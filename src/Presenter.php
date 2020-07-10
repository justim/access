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

use Access\Collection;
use Access\Database;
use Access\Entity;
use Access\Presenter\EntityPool;
use Access\Presenter\EntityPresenter;
use Access\Presenter\FutureMarker;
use Access\Presenter\MarkerInterface;
use Access\Presenter\PresentationMarker;
use ReflectionMethod;

/**
 * Present entities as simple arrays with ease
 *
 * @author Tim <me@justim.net>
 */
class Presenter
{
    /**
     * Name of the method that can receive dependencies
     */
    private const RECEIVE_DEPENDENCIES_METHOD_NAME = 'receiveDependencies';

    /**
     * Marker to indicate a cleanup is required for array
     */
    public const CLEAN_UP_ARRAY_MARKER = '__clean_up_needed__';

    /**
     * @var Database $db
     */
    private Database $db;

    /**
     * Pool of entity collections
     */
    private EntityPool $entityPool;

    /**
     * Dependencies used to inject into entity presenters
     */
    private array $dependencies = [];

    /**
     * Create a presenter
     *
     * @param Database $db
     */
    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->entityPool = new EntityPool($db);
    }

    /**
     * Add a dependency to be available for the entity presenters
     *
     * @param mixed $dependency
     */
    public function addDependency($dependency): void
    {
        $this->dependencies[get_class($dependency)] = $dependency;
    }

    /**
     * Present a entity in array form from entity
     *
     * @psalm-template TEntityPresenter of EntityPresenter
     * @psalm-param class-string<TEntityPresenter> $presenterKlass
     *
     * @param string $presenterKlass Class to present the entity with
     * @param Entity|null $entity Entity to present
     * @return array|null
     */
    public function presentEntity(string $presenterKlass, ?Entity $entity): ?array
    {
        if ($entity === null) {
            return null;
        }

        $collection = new Collection($this->db);
        $collection->addEntity($entity);

        $collectionPresentation = $this->presentCollection($presenterKlass, $collection);
        $entityPresentation = reset($collectionPresentation);

        return $entityPresentation ?? null;
    }

    /**
     * Present all entities in collection
     *
     * Empty presentations are filtered
     *
     * @psalm-template TEntityPresenter of EntityPresenter
     * @psalm-param class-string<TEntityPresenter> $presenterKlass
     *
     * @param string $presenterKlass Class to present the entity with
     * @param Collection $collection
     * @return array
     */
    public function presentCollection(string $presenterKlass, Collection $collection): array
    {
        $presenter = $this->createEntityPresenter($presenterKlass, $collection);

        $presentation = array_filter(
            $collection->map(fn(Entity $entity) => $presenter->fromEntity($entity)),
        );

        return $this->processPresentation($presentation);
    }

    /**
     * Given a array presentation, find and resolve all markers
     *
     * @param array $presentation Array presentation of some data
     * @return array Array presentation with resolved markers
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
            if (empty($markers['entities'])) {
                break;
            }

            // create a collection for all markers for each presenter and
            // present for each entity in this collection and replace the
            // marker
            $this->resolveMarkers($presentation, $markers);
        }

        $this->cleanUp($presentation);

        return $presentation;
    }

    /**
     * Provide a collection to fill the entity cache used by presenter
     *
     * @param string $entityKlass Entity class name
     * @param Collection $collection
     */
    public function provideCollection(string $entityKlass, Collection $collection): void
    {
        $this->entityPool->provideCollection($entityKlass, 'id', $collection);
    }

    /**
     * Mark a location in a presentation to be resolved later
     *
     * @param string $presenterKlass Class to present the entity with
     * @param int $id ID of the entity
     */
    public function mark(string $presenterKlass, int $id): PresentationMarker
    {
        return new PresentationMarker($presenterKlass, 'id', $id, false);
    }

    /**
     * Collect all markers left by present calls
     *
     * @return array
     */
    private function collectMarkers(array $presentation): array
    {
        $markers = [
            'presenters' => [],
            'futures' => [],
            'entities' => [],
        ];

        array_walk_recursive($presentation, function ($item) use (&$markers) {
            if (!$item instanceof MarkerInterface) {
                return;
            }

            $entityKlass = $item->getEntityKlass();
            $markers['entities'][$entityKlass][$item->getFieldName()][] = $item->getRefId();

            if ($item instanceof FutureMarker) {
                $markers['futures'][$entityKlass][$item->getFieldName()][] = $item->getRefId();
            } elseif ($item instanceof PresentationMarker) {
                $presenterKlass = $item->getPresenterKlass();
                $markers['presenters'][$presenterKlass][
                    $item->getFieldName()
                ][] = $item->getRefId();
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
        array_walk_recursive($presentation, function ($item) use (
            &$currentPresentationMarkers,
            &$currentFutureMarkers
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
        array $currentPresentationMarkers
    ): void {
        /** @psalm-var class-string<EntityPresenter> $presenterKlass */
        foreach ($markers as $presenterKlass => $info) {
            foreach ($info as $fieldName => $ids) {
                $entityKlass = $presenterKlass::getEntityKlass();
                $collection = $this->entityPool->getCollection($entityKlass, $fieldName, $ids);
                $presenter = $this->createEntityPresenter($presenterKlass, $collection);

                array_walk_recursive($presentation, function (&$item) use (
                    $presenterKlass,
                    $presenter,
                    $collection,
                    $currentPresentationMarkers
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

    private function resolveFutureMarkers(
        array &$presentation,
        array $markers,
        array $currentFutureMarkers
    ): void {
        foreach ($markers as $entityKlass => $info) {
            foreach ($info as $fieldName => $ids) {
                $collection = $this->entityPool->getCollection($entityKlass, $fieldName, $ids);

                array_walk_recursive($presentation, function (&$item) use (
                    $entityKlass,
                    $collection,
                    $currentFutureMarkers
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
     * @return mixed
     */
    private function resolvePresentationMarker(
        PresentationMarker $marker,
        Collection $collection,
        EntityPresenter $presenter
    ) {
        if (!$marker->getMultiple()) {
            $entity = $collection->find(function (Entity $entity) use ($marker) {
                return $this->matchMarker($marker, $entity);
            });

            if ($entity === null) {
                return null;
            }

            return $presenter->fromEntity($entity);
        }

        return array_values(
            array_filter(
                $collection->map(function (Entity $entity) use ($marker, $presenter) {
                    if ($this->matchMarker($marker, $entity)) {
                        return $presenter->fromEntity($entity);
                    }

                    return null;
                }),
            ),
        );
    }

    private function resolveFutureMarker(FutureMarker $marker, Collection $collection)
    {
        $callback = $marker->getCallback();

        if ($marker->getMultiple()) {
            return $callback(
                $collection->filter(function (Entity $entity) use ($marker) {
                    return $this->matchMarker($marker, $entity);
                }),
            );
        }

        $entity = $collection->find(function (Entity $entity) use ($marker) {
            return $this->matchMarker($marker, $entity);
        });

        if ($entity === null) {
            return null;
        }

        return $callback($entity);
    }

    private function matchMarker(MarkerInterface $marker, Entity $entity): bool
    {
        $values = $entity->getValues();

        if ($marker->getFieldName() === 'id') {
            return $entity->getId() === $marker->getRefId();
        }

        if (!isset($values[$marker->getFieldName()])) {
            return false;
        }

        return $values[$marker->getFieldName()] === $marker->getRefId();
    }

    /**
     * Clean up all markers that resolved to `null` in arrays
     *
     * We need to do this to prevent arrays with an non-sequential index after
     * a array_filter, causing the array to be converted to an object when
     * encoded as JSON
     *
     * ```
     * [0 => 'zero', 1 => 'one']; // in JSON: ["zero", "one"]
     * [0 => 'zero', 2 => 'two']; // in JSON: {"0": "zero", "2": "two"}
     * ```
     *
     * @param array $presentation
     */
    private function cleanUp(array &$presentation): void
    {
        foreach ($presentation as &$item) {
            if (is_array($item)) {
                if (isset($item[self::CLEAN_UP_ARRAY_MARKER])) {
                    unset($item[self::CLEAN_UP_ARRAY_MARKER]);

                    $item = array_values(array_filter($item, fn($item) => $item !== null));
                }

                $this->cleanUp($item);
            }
        }
    }

    private function createEntityPresenter(string $presenterKlass, Collection $collection)
    {
        $presenter = new $presenterKlass($this->db, $collection);

        try {
            $receiveDependencies = new ReflectionMethod(
                $presenter,
                self::RECEIVE_DEPENDENCIES_METHOD_NAME,
            );
        } catch (\Exception $e) {
            // don't care about existence
        }

        if (isset($receiveDependencies)) {
            if (!$receiveDependencies->isPublic()) {
                throw new Exception('Unsupported dependency demand: method not public');
            }

            $arguments = [];

            foreach ($receiveDependencies->getParameters() as $parameter) {
                if ($parameter->isVariadic()) {
                    throw new Exception('Unsupported dependency demand: no variadic parameters');
                }

                $type = $parameter->getType();

                if ($type === null) {
                    throw new Exception('Unsupported dependency demand: missing type');
                }

                if (isset($this->dependencies[$type->getName()])) {
                    $arguments[] = $this->dependencies[$type->getName()];
                } elseif ($type->allowsNull()) {
                    $arguments[] = null;
                } else {
                    throw new Exception(
                        sprintf(
                            'Unsupported dependency demand: "%s" not available',
                            $type->getName(),
                        ),
                    );
                }
            }

            $receiveDependencies->invokeArgs($presenter, $arguments);
        }

        return $presenter;
    }
}
