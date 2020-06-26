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

namespace Access\Presenter;

use Access\Collection;
use Access\Database;
use Access\Entity;
use Access\Presenter\PresentationMarker;

/**
 * Present entities as simple arrays with ease
 *
 * @author Tim <me@justim.net>
 * @psalm-template TEntity of Entity
 */
abstract class EntityPresenter
{
    /**
     * @var Database $db
     */
    protected Database $db;

    /**
     * Create a presenter
     *
     * @param Database $db
     * @param Collection $collection
     */
    public function __construct(Database $db, Collection $collection)
    {
        $this->db = $db;

        $this->collectFromCollection($collection);
    }

    /**
     * Get the entity klass for presenter
     *
     * @psalm-return class-string<TEntity>
     * @return string Entity klass
     */
    abstract public static function getEntityKlass(): string;

    /**
     * Create array representation from entity
     *
     * @param Entity $entity Entity
     * @return array|null Array representation
     */
    abstract public function fromEntity(Entity $entity): ?array;

    /**
     * Collect ref data for collection
     *
     * @param Collection $collection
     */
    protected function collectFromCollection(Collection $collection): void
    {
        // empty implementation
    }

    /**
     * Present a entity in array form from ID
     *
     * ID must be present in collection
     *
     * @psalm-template TEntityPresenter of EntityPresenter
     * @psalm-param class-string<TEntityPresenter> $presenterKlass
     *
     * @param string $presenterKlass Class to present the entity with
     * @param int|null $id ID of the entity
     * @return PresentationMarker|null Marker with presenter info
     */
    protected function present(string $presenterKlass, ?int $id): ?PresentationMarker
    {
        return $this->presentInversedRef($presenterKlass, 'id', $id);
    }

    /**
     * Present mulitple entity in array form from list of IDs
     *
     * Empty presentations are filtered
     *
     * @psalm-template TEntityPresenter of EntityPresenter
     * @psalm-param class-string<TEntityPresenter> $presenterKlass
     *
     * @param string $presenterKlass Class to present the collection with
     * @param int[] $ids
     * @return array
     */
    protected function presentMultiple(string $presenterKlass, array $ids): array
    {
        $this->db->assertValidPresenterClass($presenterKlass);

        return array_values(
            array_filter(
                array_map(function ($id) use ($presenterKlass) {
                    return $this->present($presenterKlass, $id);
                }, $ids),
            ),
        );
    }

    /**
     * Present a entity in array form from any field name
     *
     * Field name must be present in entity
     *
     * @psalm-template TEntityPresenter of EntityPresenter
     * @psalm-param class-string<TEntityPresenter> $presenterKlass
     *
     * @param string $presenterKlass Class to present the entity with
     * @param string $fieldName Name of referenced field
     * @param int|null $id ID of the entity
     * @return PresentationMarker|null Marker with presenter info
     */
    protected function presentInversedRef(
        string $presenterKlass,
        string $fieldName,
        ?int $id
    ): ?PresentationMarker {
        $this->db->assertValidPresenterClass($presenterKlass);

        if ($id === null) {
            return null;
        }

        return new PresentationMarker($presenterKlass, $fieldName, $id, false);
    }

    /**
     * Present a entity in array form from any field name
     *
     * Field name must be present in entity
     *
     * @psalm-template TEntityPresenter of EntityPresenter
     * @psalm-param class-string<TEntityPresenter> $presenterKlass
     *
     * @param string $presenterKlass Class to present the entity with
     * @param string $fieldName Name of referenced field
     * @param int|null $id ID of the entity
     * @return PresentationMarker|null Marker with presenter info
     */
    protected function presentMultipleInversedRefs(
        string $presenterKlass,
        string $fieldName,
        ?int $id
    ): ?PresentationMarker {
        $this->db->assertValidPresenterClass($presenterKlass);

        if ($id === null) {
            return null;
        }

        return new PresentationMarker($presenterKlass, $fieldName, $id, true);
    }

    /**
     * Create a future presentation for entity with ID
     *
     * Callback will be called when the future is resolved
     *
     * @psalm-template TEntity of Entity
     * @psalm-param class-string<TEntity> $entityKlass
     *
     * @param string $entityKlass Entity class name
     * @param int|null $id ID of the entity
     * @param \Closure $callback On resolved callback
     * @return FutureMarker|null Marker with future presentation info
     */
    protected function presentFuture(
        string $entityKlass,
        ?int $id,
        \Closure $callback
    ): ?FutureMarker {
        $this->db->assertValidEntityClass($entityKlass);

        return $this->presentFutureInversedRef($entityKlass, 'id', $id, $callback);
    }

    /**
     * Create a future presentation for entity with field name
     *
     * Callback will be called when the future is resolved
     *
     * @psalm-template TEntity of Entity
     * @psalm-param class-string<TEntity> $entityKlass
     *
     * @param string $entityKlass Entity class name
     * @param string $fieldName Name of referenced field
     * @param int|null $id ID of the entity
     * @param \Closure $callback On resolved callback
     * @return FutureMarker|null Marker with future presentation info
     */
    protected function presentFutureInversedRef(
        string $entityKlass,
        string $fieldName,
        ?int $id,
        \Closure $callback
    ): ?FutureMarker {
        $this->db->assertValidEntityClass($entityKlass);

        if ($id === null) {
            return null;
        }

        return new FutureMarker($entityKlass, $fieldName, $id, false, $callback);
    }

    /**
     * Create a future presentation for multiple entities with field name
     *
     * Callback will be called when the future is resolved
     *
     * @psalm-template TEntity of Entity
     * @psalm-param class-string<TEntity> $entityKlass
     *
     * @param string $entityKlass Entity class name
     * @param string $fieldName Name of referenced field
     * @param int|null $id ID of the entity
     * @param \Closure $callback On resolved callback
     * @return FutureMarker|null Marker with future presentation info
     */
    protected function presentFutureMultipleInversedRefs(
        string $entityKlass,
        string $fieldName,
        ?int $id,
        \Closure $callback
    ): ?FutureMarker {
        $this->db->assertValidEntityClass($entityKlass);

        if ($id === null) {
            return null;
        }

        return new FutureMarker($entityKlass, $fieldName, $id, true, $callback);
    }

    /**
     * Present a date
     *
     * @param \DateTimeInterface|null $date
     * @return string|null
     */
    protected function presentDate(?\DateTimeInterface $date): ?string
    {
        if ($date === null) {
            return null;
        }

        return $date->format('Y-m-d');
    }

    /**
     * Present a date time
     *
     * @param \DateTimeInterface|null $dateTime
     * @return string|null
     */
    protected function presentDateTime(?\DateTimeInterface $dateTime): ?string
    {
        if ($dateTime === null) {
            return null;
        }

        return $dateTime->format(\DateTime::ATOM);
    }
}