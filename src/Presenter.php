<?php

/*
 * This file is part of the Access package.
 *
 * (c) Tim <me@justim.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Access;

use Access\Clause\ClauseInterface;
use Access\Clause\ConditionInterface;
use Access\Clause\FilterInterface;
use Access\Clause\OrderByInterface;
use Access\Presenter\CustomMarkerInterface;
use Access\Presenter\EntityPool;
use Access\Presenter\EntityPresenter;
use Access\Presenter\FutureMarker;
use Access\Presenter\EntityMarkerInterface;
use Access\Presenter\PresentationMarker;

/**
 * Present entities as simple arrays with ease
 *
 * @author Tim <me@justim.net>
 */
class Presenter
{
    private Database $db;

    /**
     * Pool of entity collections
     */
    private EntityPool $entityPool;

    /**
     * Dependencies used to inject into entity presenters
     *
     * @var array<string, mixed>
     */
    private array $dependencies = [];

    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->entityPool = new EntityPool($db);

        $this->dependencies[Database::class] = $db;
    }

    /**
     * Add a dependency to be available for the entity presenters
     */
    public function addDependency(object $dependency): void
    {
        $this->dependencies[get_class($dependency)] = $dependency;
    }

    /**
     * Present an entity in array form from entity
     *
     * @psalm-template TEntityPresenterEntity of Entity
     * @psalm-template TEntityPresenter of EntityPresenter<TEntityPresenterEntity>
     * @psalm-param class-string<TEntityPresenter> $presenterKlass
     *
     * @param string $presenterKlass Class to present the entity with
     * @param Entity|null $entity Entity to present
     * @return array<string, mixed>|null
     */
    public function presentEntity(string $presenterKlass, ?Entity $entity): ?array
    {
        $this->db->assertValidPresenterClass($presenterKlass);

        if ($entity === null) {
            return null;
        }

        $collection = new Collection($this->db);
        $collection->addEntity($entity);

        $collectionPresentation = $this->presentCollection($presenterKlass, $collection);

        if (empty($collectionPresentation)) {
            return null;
        }

        $entityPresentation = reset($collectionPresentation);

        return $entityPresentation ?: null;
    }

    /**
     * Present all entities in collection
     *
     * Empty presentations are filtered
     *
     * @psalm-template TEntityPresenterEntity of Entity
     * @psalm-template TEntityPresenter of EntityPresenter<TEntityPresenterEntity>
     * @psalm-param class-string<TEntityPresenter> $presenterKlass
     * @psalm-template TEntity of Entity
     *
     * @psalm-param Collection<TEntity> $collection
     * @param string $presenterKlass Class to present the entity with
     * @return array<array-key, array<string, mixed>>
     * @psalm-return array<array-key, array<string, mixed>>
     */
    public function presentCollection(string $presenterKlass, Collection $collection): array
    {
        $this->db->assertValidPresenterClass($presenterKlass);

        $presenter = $this->createEntityPresenter($presenterKlass);

        $presentation = array_values(
            array_filter($collection->map(fn(Entity $entity) => $presenter->fromEntity($entity))),
        );

        return $this->processPresentation($presentation);
    }

    /**
     * Given an array presentation, find and resolve all markers
     *
     * @psalm-template T of array<array-key, array<string, mixed>>|array<string, mixed>
     * @param array $presentation Array presentation of some data
     * @return array Array presentation with resolved markers
     * @psalm-param T $presentation Array presentation of some data
     * @psalm-return T Array presentation with resolved markers
     */
    public function processPresentation(array $presentation): array
    {
        $i = 0;

        while (true) {
            if (++$i === 100) {
                throw new Exception(
                    'Presenter loop detected, this likely happens when a future marker keeps returning a future marker',
                );
            }

            // collect all markers left by the present calls
            $markers = $this->collectMarkers($presentation);

            // there are no markers left in the presentation to be resolved
            if (empty($markers['entities']) && empty($markers['custom'])) {
                break;
            }

            // create a collection for all markers for each presenter and
            // present for each entity in this collection and replace the
            // marker
            $this->resolveMarkers($presentation, $markers);
        }

        /**
         * @var array<array-key, array<string, mixed>>|array<string, mixed> $presentation
         * @psalm-var T $presentation
         */
        return $presentation;
    }

    /**
     * Provide a collection to fill the entity cache used by presenter
     *
     * @psalm-template TEntity of Entity
     * @psalm-param class-string<TEntity> $entityKlass
     * @param string $entityKlass Entity class name
     * @psalm-param Collection<TEntity> $collection
     */
    public function provideCollection(string $entityKlass, Collection $collection): void
    {
        $this->entityPool->provideCollection($entityKlass, 'id', $collection);
    }

    /**
     * Mark a location in a presentation to be resolved later
     *
     * @psalm-template TEntityPresenterEntity of Entity
     * @psalm-template TEntityPresenter of EntityPresenter<TEntityPresenterEntity>
     * @psalm-param class-string<TEntityPresenter> $presenterKlass
     * @param string $presenterKlass Class to present the entity with
     * @param int|int[]|Entity|Collection $id ID(s) of the entity
     */
    public function mark(
        string $presenterKlass,
        int|array|Entity|Collection $id,
        ?ClauseInterface $clause = null,
    ): PresentationMarker {
        $id = $id instanceof Collection ? $id->getIds() : $id;
        $id = $id instanceof Entity ? $id->getId() : $id;

        return new PresentationMarker($presenterKlass, 'id', $id, is_array($id), $clause);
    }

    /**
     * Collect all markers left by present calls
     *
     * @return array<string, array>
     * @psalm-return array{presenters: array<class-string, array<string, int[]>>, futures: array<class-string, array<string, int[]>>, entities: array<class-string, array<string, int[]>>, custom: mixed[]}
     */
    private function collectMarkers(array $presentation): array
    {
        /** @psalm-var array{presenters: array<class-string, array<string, int[]>>, futures: array<class-string, array<string, int[]>>, entities: array<class-string, array<string, int[]>>, custom: mixed[]} $markers */
        $markers = [
            'presenters' => [],
            'futures' => [],
            'entities' => [],
            'custom' => [],
        ];

        array_walk_recursive($presentation, function (mixed $item) use (&$markers) {
            /** @psalm-var array{presenters: array<class-string, array<string, int[]>>, futures: array<class-string, array<string, int[]>>, entities: array<class-string, array<string, int[]>>, custom: mixed[]} $markers */

            if ($item instanceof CustomMarkerInterface) {
                $markers['custom'][] = $item;
            }

            if (!$item instanceof EntityMarkerInterface) {
                return;
            }

            $entityKlass = $item->getEntityKlass();

            if (!isset($markers['entities'][$entityKlass][$item->getFieldName()])) {
                $markers['entities'][$entityKlass][$item->getFieldName()] = [];
            }

            array_push(
                $markers['entities'][$entityKlass][$item->getFieldName()],
                ...$item->getRefIds(),
            );

            if ($item instanceof FutureMarker) {
                if (!isset($markers['futures'][$entityKlass][$item->getFieldName()])) {
                    $markers['futures'][$entityKlass][$item->getFieldName()] = [];
                }

                array_push(
                    $markers['futures'][$entityKlass][$item->getFieldName()],
                    ...$item->getRefIds(),
                );
            } elseif ($item instanceof PresentationMarker) {
                $presenterKlass = $item->getPresenterKlass();

                if (!isset($markers['presenters'][$presenterKlass][$item->getFieldName()])) {
                    $markers['presenters'][$presenterKlass][$item->getFieldName()] = [];
                }

                array_push(
                    $markers['presenters'][$presenterKlass][$item->getFieldName()],
                    ...$item->getRefIds(),
                );
            }
        });

        return $markers;
    }

    /**
     * Resolve all markers found
     *
     * @param array $presentation Current presentation data
     * @param array $markers List of markers to resolve in presentation data
     */
    private function resolveMarkers(array &$presentation, array $markers): void
    {
        foreach ($markers['entities'] as $entityKlass => $info) {
            foreach ($info as $fieldName => $ids) {
                $this->entityPool->getCollection($entityKlass, $fieldName, $ids);
            }
        }

        $currentPresentationMarkers = [];
        $currentFutureMarkers = [];

        // make sure we only resolve markers currently known in the
        // presentation, resolving markers could give back a bunch of new
        // markers that are not yet ready to be processed, use the next loop to
        // resolve these
        array_walk_recursive($presentation, function (mixed $item) use (
            &$currentPresentationMarkers,
            &$currentFutureMarkers,
        ) {
            if ($item instanceof PresentationMarker) {
                $currentPresentationMarkers[] = $item;
            } elseif ($item instanceof FutureMarker) {
                $currentFutureMarkers[] = $item;
            }
        });

        $this->resolvePresentationMarkers(
            $presentation,
            $markers['presenters'],
            $currentPresentationMarkers,
        );

        $this->resolveFutureMarkers($presentation, $markers['futures'], $currentFutureMarkers);

        $this->resolveCustomMarkers($presentation);
    }

    /**
     * Resolve presentation markers
     *
     * @param array $presentation Current presentation data
     * @param array $markers List of markers to resolve in presentation
     * @param array $currentPresentationMarkers List of presentation markers currently in presentation data
     */
    private function resolvePresentationMarkers(
        array &$presentation,
        array $markers,
        array $currentPresentationMarkers,
    ): void {
        /** @psalm-var class-string<EntityPresenter> $presenterKlass */
        foreach ($markers as $presenterKlass => $info) {
            foreach ($info as $fieldName => $ids) {
                $entityKlass = $presenterKlass::getEntityKlass();
                $collection = $this->entityPool->getCollection($entityKlass, $fieldName, $ids);
                $presenter = $this->createEntityPresenter($presenterKlass);

                array_walk_recursive($presentation, function (mixed &$item) use (
                    $presenterKlass,
                    $presenter,
                    $collection,
                    $currentPresentationMarkers,
                ) {
                    if (
                        $item instanceof PresentationMarker &&
                        $item->getPresenterKlass() === $presenterKlass &&
                        in_array($item, $currentPresentationMarkers, true)
                    ) {
                        $item = $this->resolvePresentationMarker($item, $collection, $presenter);
                    }
                });
            }
        }
    }

    /**
     * Resolve future markers
     *
     * @param array $presentation Current presentation data
     * @param array $markers List of markers to resolve in presentation
     * @param array $currentFutureMarkers List of future markers currently in presentation data
     */
    private function resolveFutureMarkers(
        array &$presentation,
        array $markers,
        array $currentFutureMarkers,
    ): void {
        foreach ($markers as $entityKlass => $info) {
            foreach ($info as $fieldName => $ids) {
                $collection = $this->entityPool->getCollection($entityKlass, $fieldName, $ids);

                array_walk_recursive($presentation, function (mixed &$item) use (
                    $entityKlass,
                    $collection,
                    $currentFutureMarkers,
                ) {
                    if (
                        $item instanceof FutureMarker &&
                        $item->getEntityKlass() === $entityKlass &&
                        in_array($item, $currentFutureMarkers, true)
                    ) {
                        $item = $this->resolveFutureMarker($item, $collection);
                    }
                });
            }
        }
    }

    /**
     * Resolve a single presentation marker
     *
     * @param PresentationMarker $marker Presentation marker to resolve
     * @param Collection $collection Collection that contains the entit(y|ies) linked to marker
     * @param EntityPresenter $presenter Presenter to convert marker to array
     */
    private function resolvePresentationMarker(
        PresentationMarker $marker,
        Collection $collection,
        EntityPresenter $presenter,
    ): ?array {
        return $this->resolveMarker(
            $marker,
            $collection,
            fn(Entity $entity) => $presenter->fromEntity($entity),
            fn(Collection $entities) => array_values(
                array_filter($entities->map(fn(Entity $entity) => $presenter->fromEntity($entity))),
            ),
        );
    }

    /**
     * Resolve a single future marker
     *
     * @param FutureMarker $marker Future marker to resolve
     * @param Collection $collection Collection that contains the entit(y|ies) linked to marker
     */
    private function resolveFutureMarker(FutureMarker $marker, Collection $collection): mixed
    {
        $callback = $marker->getCallback();

        return $this->resolveMarker($marker, $collection, $callback, $callback);
    }

    /**
     * Resolve a single marker
     *
     * @psalm-param callable(Entity): mixed $callbackSingle
     * @psalm-param callable(Collection): mixed $callbackMultiple
     *
     * @param EntityMarkerInterface $marker Marker to resolve
     * @param Collection $collection Collection that contains the entit(y|ies) linked to marker
     * @param callable $callbackSingle Called when the marker is single
     * @param callable $callbackMultiple Called when the marker is multiple
     */
    private function resolveMarker(
        EntityMarkerInterface $marker,
        Collection $collection,
        callable $callbackSingle,
        callable $callbackMultiple,
    ): mixed {
        if (!$marker->getMultiple()) {
            $entity = $collection->find(function (Entity $entity) use ($marker) {
                return $this->matchMarker($marker, $entity);
            });

            if ($entity === null) {
                return null;
            }

            return $callbackSingle($entity);
        }

        $entities = $collection->filter(fn(Entity $entity) => $this->matchMarker($marker, $entity));

        $clause = $marker->getClause();

        if ($clause !== null) {
            if ($clause instanceof FilterInterface) {
                $entities = $clause->filterCollection($entities);
            }

            if ($clause instanceof OrderByInterface) {
                $entities = $entities->applyClause($clause);
            }
        }

        return $callbackMultiple($entities);
    }

    /**
     * Match a marker against an entity
     *
     * Uses the field name and the optionally some clauses in the marker
     */
    private function matchMarker(EntityMarkerInterface $marker, Entity $entity): bool
    {
        $values = array_merge($entity->getValues(), [
            'id' => $entity->getId(),
        ]);

        if (!isset($values[$marker->getFieldName()])) {
            return false;
        }

        if (!in_array($values[$marker->getFieldName()], $marker->getRefIds(), true)) {
            return false;
        }

        $clause = $marker->getClause();

        if ($clause !== null) {
            if ($clause instanceof ConditionInterface) {
                if (!$clause->matchesEntity($entity)) {
                    return false;
                }
            }
        }

        return true;
    }

    private function resolveCustomMarkers(array &$presentation): void
    {
        array_walk_recursive($presentation, function (mixed &$item) {
            if ($item instanceof CustomMarkerInterface) {
                /** @psalm-suppress MixedAssignment */
                $item = $item->fetch();
            }
        });
    }

    /**
     * Create an entity presenter instance
     *
     * Will resolve dependencies by injecting them into the constructor
     *
     * @psalm-template TEntityPresenter of EntityPresenter
     * @psalm-param class-string<TEntityPresenter> $presenterKlass
     *
     * @param string $presenterKlass Class to present the entity/collection
     */
    private function createEntityPresenter(string $presenterKlass): EntityPresenter
    {
        try {
            $klassReflection = new \ReflectionClass($presenterKlass);
            $constructorMethod = $klassReflection->getConstructor();
        } catch (\ReflectionException) {
            // don't care about existence
        }

        if (isset($constructorMethod)) {
            $arguments = $this->matchDependencies($constructorMethod);
            /** @psalm-suppress UnsafeInstantiation */
            $entityPresenter = new $presenterKlass(...$arguments);
        } else {
            /** @psalm-suppress UnsafeInstantiation */
            $entityPresenter = new $presenterKlass();

            try {
                // still possible for backwards compatibility
                $receiveDependencies = new \ReflectionMethod(
                    $entityPresenter,
                    'receiveDependencies',
                );

                $arguments = $this->matchDependencies($receiveDependencies);

                $receiveDependencies->invokeArgs($entityPresenter, $arguments);
            } catch (\ReflectionException) {
                // don't care about existence
            }
        }

        return $entityPresenter;
    }

    /**
     * Match dependencies of this presenter to arguments of given method
     *
     * Dependency injection, if you will
     *
     * @param \ReflectionMethod $method Method to match arguments for
     * @return mixed[] List of dependencies
     */
    private function matchDependencies(\ReflectionMethod $method): array
    {
        if (!$method->isPublic()) {
            throw new Exception('Unsupported dependency demand: method not public');
        }

        $arguments = [];

        foreach ($method->getParameters() as $parameter) {
            if ($parameter->isVariadic()) {
                throw new Exception('Unsupported dependency demand: no variadic parameters');
            }

            $type = $parameter->getType();

            if ($type === null || !$type instanceof \ReflectionNamedType) {
                throw new Exception('Unsupported dependency demand: missing valid type');
            }

            $typeName = $type->getName();

            if (isset($this->dependencies[$typeName])) {
                /** @psalm-suppress MixedAssignment */
                $arguments[] = $this->dependencies[$typeName];
                continue;
            }

            /** @var mixed $dependency */
            foreach ($this->dependencies as $dependency) {
                if ($dependency instanceof $typeName) {
                    /** @psalm-suppress MixedAssignment */
                    $arguments[] = $dependency;

                    // goto next dependency
                    continue 2;
                }
            }

            if ($type->allowsNull()) {
                $arguments[] = null;
                continue;
            }

            throw new Exception(
                sprintf('Unsupported dependency demand: "%s" not available', $typeName),
            );
        }

        return $arguments;
    }
}
