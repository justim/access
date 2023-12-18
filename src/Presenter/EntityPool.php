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

/**
 * Hold a pool of entities
 *
 * @psalm-template TEntity of Entity
 * @author Tim <me@justim.net>
 */
final class EntityPool
{
    private Database $db;

    /**
     * @var array<string, array<string, Collection>>
     * @psalm-var array<class-string<TEntity>, array<string, Collection<TEntity>>>
     */
    private array $entities = [];

    /**
     * Create a entity pool
     *
     * @param Database $db Database connection
     */
    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Provide a collection to the entity pool
     *
     * @psalm-param class-string<TEntity> $entityKlass
     *
     * @param string $entityKlass Entity class name
     * @param string $fieldName Name of referenced field
     * @param Collection<TEntity> $collection Provided collection
     */
    public function provideCollection(
        string $entityKlass,
        string $fieldName,
        Collection $collection,
    ): void {
        $currentCollection = $this->getOrCreateCurrentCollection($entityKlass, $fieldName);
        $currentCollection->merge($collection);
    }

    /**
     * Get a collection from the pool by ID
     *
     * @psalm-param class-string<TEntity> $entityKlass
     *
     * @param string $entityKlass Entity class name
     * @param string $fieldName Name of referenced field
     * @param array $ids List of IDs
     * @return Collection
     */
    public function getCollection(string $entityKlass, string $fieldName, array $ids): Collection
    {
        $currentCollection = $this->getOrCreateCurrentCollection($entityKlass, $fieldName);

        $currentIds = $currentCollection->map(
            fn(Entity $entity): mixed => $this->getValue($entity, $fieldName),
        );

        $newIds = array_unique(array_diff($ids, $currentIds));

        // when new ids are requested, grow the pool
        if (!empty($newIds)) {
            $repo = $this->db->getRepository($entityKlass);

            $newCollection = $repo->findByAsCollection([
                $fieldName => $newIds,
            ]);

            $currentCollection->merge($newCollection);
        }

        // create a subset collection with only the entities requested by ID
        return $currentCollection->filter(function (Entity $entity) use ($fieldName, $ids) {
            $id = $this->getValue($entity, $fieldName);

            return in_array($id, $ids);
        });
    }

    /**
     * Get the value of a field from an entity
     *
     * @param Entity $entity Entity to query
     * @param string $fieldName Name of referenced field
     * @return int|null
     */
    private function getValue(Entity $entity, string $fieldName): ?int
    {
        $values = $entity->getValues();

        if ($fieldName === 'id') {
            return $entity->getId();
        }

        if (!isset($values[$fieldName])) {
            return null;
        }

        if (!is_int($values[$fieldName])) {
            return null;
        }

        return $values[$fieldName];
    }

    /**
     * Get or create a new collection for entity klass
     *
     * @psalm-param class-string<TEntity> $entityKlass
     * @param string $entityKlass Entity class name
     * @param string $fieldName Name of referenced field
     * @return Collection<TEntity>
     */
    private function getOrCreateCurrentCollection(
        string $entityKlass,
        string $fieldName,
    ): Collection {
        if (!isset($this->entities[$entityKlass][$fieldName])) {
            /** @var Collection<TEntity> $collection */
            $collection = new Collection($this->db);
            $this->entities[$entityKlass][$fieldName] = $collection;
        }

        return $this->entities[$entityKlass][$fieldName];
    }
}
