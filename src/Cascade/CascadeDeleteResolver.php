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

namespace Access\Cascade;

use Access\Cascade;
use Access\Clause\Condition\Equals;
use Access\Clause\Condition\NotIn;
use Access\Database;
use Access\DeleteKind;
use Access\Entity;
use Access\Exception\NotSupportedException;
use Access\Query;
use Access\Schema\Type;

/**
 * Resolve the order of cascading delete operations
 *
 * @author Tim <me@justim.net>
 */
class CascadeDeleteResolver
{
    /**
     * The database to find/delete entities
     * @var Database $db
     */
    private Database $db;

    /**
     * The initial entity that started it all
     * @var Entity
     */
    private Entity $initialEntity;

    /**
     * The initial delete kind
     * @var DeleteKind
     */
    private DeleteKind $initialDeleteKind;

    /**
     * The time at which the soft deletes should be marked as deleted
     * @var \DateTimeInterface
     */
    private \DateTimeInterface $softDeletedAt;

    /**
     * Administer the dependencies between the entities
     * @var array<class-string<Entity>, array<class-string<Entity>, DeleteKind>>
     */
    private array $dependsOn = [];

    /**
     * List of entities that should be deleted
     * @var array<class-string<Entity>, array<int>>
     */
    private array $toDeleteRegular = [];

    /**
     * List of entities that should be soft deleted
     * @var array<class-string<Entity>, array<int>>
     */
    private array $toDeleteSoft = [];

    /**
     * @param Database $db The database to find/delete entities
     * @param Entity $entity The initail entity that starts it all
     * @param DeleteKind $initialDeleteKind The initial delete kind
     */
    public function __construct(Database $db, Entity $entity, DeleteKind $initialDeleteKind)
    {
        $this->db = $db;
        $this->initialEntity = $entity;
        $this->initialDeleteKind = $initialDeleteKind;

        // keep the all the soft deletes in a transaction at the same moment in time
        $this->softDeletedAt = $this->db->now();

        if ($initialDeleteKind === DeleteKind::Soft) {
            $this->toDeleteSoft[$entity::class] = [$entity->getId()];
        } else {
            $this->toDeleteRegular[$entity::class] = [$entity->getId()];
        }
    }

    /**
     * Execute the delete operations
     *
     * @return bool The initial entity was deleted
     */
    public function execute(): bool
    {
        $this->resolve();

        $updated = false;

        /** @var class-string<Entity> $initialEntityKlass */
        $initialEntityKlass = get_class($this->initialEntity);

        foreach ($this->toDeleteSoft as $klass => $ids) {
            $updated_ = $this->softDelete($klass, $ids);

            if ($klass === $initialEntityKlass) {
                // assume original entity is among the soft deleted entities
                $updated = $updated_;
            }
        }

        foreach ($this->toDeleteRegular as $klass => $ids) {
            $updated_ = $this->delete($klass, $ids);

            if ($klass === $initialEntityKlass) {
                // assume original entity is among the deleted entities
                $updated = $updated_;
            }
        }

        if ($this->initialDeleteKind === DeleteKind::Soft) {
            // technically the entity is still valid, we just mark it as deleted
            $this->initialEntity->markUpdated([
                'deleted_at' => $this->softDeletedAt,
            ]);
        }

        return $updated;
    }

    /**
     * Resolve the order of cascading delete operations
     */
    public function resolve(): void
    {
        $this->resolveEntity($this->initialEntity, $this->initialDeleteKind);

        if (empty($this->toDeleteRegular)) {
            // all deletes are soft deletes, so we can just delete them. no issues with foreign keys
            return;
        }

        // naively sort the entities by their dependencies
        uksort($this->toDeleteRegular, function (string $a, string $b): int {
            // there is a cycle
            if (isset($this->dependsOn[$a][$b]) && isset($this->dependsOn[$b][$a])) {
                // the cycle is soft, so we can ignore it
                if (
                    $this->dependsOn[$a][$b] === DeleteKind::Soft &&
                    $this->dependsOn[$b][$a] === DeleteKind::Soft
                ) {
                    return 0;
                }

                // a wants to go first, but it's soft, so we allow b to go before a
                if ($this->dependsOn[$a][$b] === DeleteKind::Soft) {
                    return 1;
                }

                // b wants to go first, but it's soft, so we allow a to go before b
                if ($this->dependsOn[$b][$a] === DeleteKind::Soft) {
                    return -1;
                }

                // two hard deletes cannot be resolved, it will fail with a foreign key constraint error
                throw new CascadeDeleteCycleException(
                    'Cannot resolve dependencies for ' . $a . ' and ' . $b,
                );
            }

            // a needs to go before b
            if (isset($this->dependsOn[$a][$b])) {
                return -1;
            }

            // b needs to go before a
            if (isset($this->dependsOn[$b][$a])) {
                return 1;
            }

            return 0;
        });
    }

    /**
     * Resolve a single entity
     *
     * @param Entity $entity
     * @param DeleteKind $deleteKind
     */
    private function resolveEntity(Entity $entity, DeleteKind $deleteKind): void
    {
        $this->deleteFields($entity, $deleteKind);
        $this->deleteRelations($entity, $deleteKind);
    }

    /**
     * Dive deeper into the relations
     *
     * Recursively resolve the relations of the entities
     *
     * @param \Generator<Entity> $entities
     * @param DeleteKind $kind
     * @param class-string<Entity> $target
     * @param Cascade $cascade
     */
    private function resolveRelation(
        \Generator $entities,
        DeleteKind $kind,
        string $target,
        Cascade $cascade,
    ): void {
        foreach ($entities as $entity) {
            if ($cascade->shouldCascadeDeleteSoft($kind, $target)) {
                $this->toDeleteSoft[$target][] = $entity->getId();

                $this->resolveEntity($entity, DeleteKind::Soft);
            } elseif ($cascade->shouldCascadeDeleteRegular($kind)) {
                $this->toDeleteRegular[$target][] = $entity->getId();

                $this->resolveEntity($entity, DeleteKind::Regular);
            }
        }
    }

    /**
     * Track a dependency between two entities
     *
     * @param class-string<Entity> $first
     * @param class-string<Entity> $second
     */
    private function dependsOn(
        string $first,
        string $second,
        DeleteKind $kind,
        Cascade $cascade,
    ): void {
        $dependsOnKind = null;

        if ($cascade->shouldCascadeDeleteSoft($kind, $second)) {
            $dependsOnKind = DeleteKind::Soft;
        } elseif ($cascade->shouldCascadeDeleteRegular($kind)) {
            $dependsOnKind = DeleteKind::Regular;
        }

        if ($dependsOnKind === null) {
            return;
        }

        if (!isset($this->dependsOn[$first][$second])) {
            $this->dependsOn[$first][$second] = $dependsOnKind;
            return;
        }

        // already regular, setting it to soft is not possible
        if ($this->dependsOn[$first][$second] === DeleteKind::Regular) {
            return;
        }

        $this->dependsOn[$first][$second] = $dependsOnKind;
    }

    private function deleteFields(Entity $entity, DeleteKind $kind): void
    {
        $fields = $entity::getTableSchema()->getFields();
        $values = $entity->getValues();

        foreach ($fields as $field) {
            $type = $field->getType();

            // only fields marked as a reference
            if (!$type instanceof Type\Reference) {
                continue;
            }

            // skip fields that are not set (or `null`)
            if (!isset($values[$field->getName()])) {
                continue;
            }

            $cascade = $type->getCascade();

            if ($cascade === null) {
                continue;
            }

            $target = $type->getTarget();

            if (!is_string($target) || !is_subclass_of($target, Entity::class)) {
                throw new NotSupportedException('Cascading delete only works for entity relations');
            }

            if ($cascade->shouldCascadeDelete($kind, $target)) {
                $r = $this->find($target, 'id', $values[$field->getName()], $kind);

                $this->resolveRelation($r, $kind, $target, $cascade);
            }
        }
    }

    /**
     * @param Entity $entity
     * @param DeleteKind $kind
     */
    private function deleteRelations(Entity $entity, DeleteKind $kind): void
    {
        $id = $entity->getId();
        $relations = $entity::relations();

        foreach ($relations as $relation) {
            /** @var Cascade|null $cascade */
            $cascade = $relation['cascade'] ?? null;

            if ($cascade === null) {
                continue;
            }

            /** @phpstan-ignore isset.offset */
            if (!isset($relation['target'])) {
                continue;
            }

            $target = $relation['target'];

            if ($cascade->shouldCascadeDelete($kind, $target)) {
                $r = $this->find($target, $relation['field'], $id, $kind);

                $this->dependsOn($target, $entity::class, $kind, $cascade);

                $this->resolveRelation($r, $kind, $target, $cascade);
            }
        }
    }

    /**
     * @param class-string<Entity> $klass
     * @param string $field
     * @param mixed $id
     * @param DeleteKind $kind
     * @return \Generator<Entity>
     */
    private function find(string $klass, string $field, mixed $id, DeleteKind $kind): \Generator
    {
        $select = new Query\Select($klass);

        $select->where(new Equals($field, $id));

        $ids = match ($kind) {
            DeleteKind::Regular => $this->toDeleteRegular[$klass] ?? [],
            DeleteKind::Soft => $this->toDeleteSoft[$klass] ?? [],
        };

        if (count($ids) > 0) {
            // dont include the entities that are already going to be deleted, prevents infinite loops
            $select->where(new NotIn('id', $ids));
        }

        yield from $this->db->select($klass, $select);
    }

    /**
     * @param class-string<Entity> $klass
     * @param int[] $ids
     */
    private function delete(string $klass, array $ids): bool
    {
        $query = new Query\Delete($klass::tableName());

        $query->where([
            'id IN (?)' => $ids,
        ]);

        $gen = $this->db->executeStatement($query);
        $updated = $gen->getReturn() > 0;

        return $updated;
    }

    /**
     * @param class-string<Entity> $klass
     * @param int[] $ids
     */
    private function softDelete(string $klass, array $ids): bool
    {
        $query = new Query\Update($klass::tableName());

        $query->values([
            'deleted_at' => $this->softDeletedAt,
        ]);

        $query->where([
            'id IN (?)' => $ids,
        ]);

        $gen = $this->db->executeStatement($query);
        $updated = $gen->getReturn() > 0;
        return $updated;
    }
}
