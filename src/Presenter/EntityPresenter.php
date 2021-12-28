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

use Access\Clause\ClauseInterface;
use Access\Collection;
use Access\Database;
use Access\Entity;
use Access\Presenter;
use Access\Presenter\FutureMarker;
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
     * @return array<string, mixed>|null Array representation
     */
    abstract public function fromEntity(Entity $entity): ?array;

    /**
     * Present a entity in array form from ID
     *
     * ID must be present in collection
     *
     * ```php
     * // Will create the `SomeUserPresenter` presenter and call it with the
     * // entity associated with the presenter and primary ID `$userId`
     * 'user' => $this->present(
     *      SomeUserPresenter::class,
     *      $userId,
     * );
     * ```
     *
     * @psalm-template TEntityPresenter of EntityPresenter
     * @psalm-param class-string<TEntityPresenter> $presenterKlass
     *
     * @param string $presenterKlass Class to present the entity with
     * @param int|null $id ID of the entity
     * @param ClauseInterface|null $clause Optional clause to manipulate resulting entity
     * @return PresentationMarker|null Marker with presenter info
     */
    protected function present(
        string $presenterKlass,
        ?int $id,
        ?ClauseInterface $clause = null
    ): ?PresentationMarker {
        return $this->presentInversedRef($presenterKlass, 'id', $id, $clause);
    }

    /**
     * Present mulitple entity in array form from list of IDs
     *
     * Empty presentations are filtered
     *
     * ```php
     * // Will create the `SomeUserPresenter` presenter and call it with the
     * // entities associated with the presenter and primary IDs `$ids`
     * 'users' => $this->presentMultiple(
     *      SomeUserPresenter::class,
     *      $userIds,
     * );
     * ```
     *
     * @psalm-template TEntityPresenter of EntityPresenter
     * @psalm-param class-string<TEntityPresenter> $presenterKlass
     *
     * @param string $presenterKlass Class to present the collection with
     * @param int[] $ids Primary IDs of entities associated with presenter
     * @return array List of presentation markers
     */
    protected function presentMultiple(string $presenterKlass, array $ids): array
    {
        Database::assertValidPresenterClass($presenterKlass);

        $result = array_values(
            array_filter(
                array_map(fn($id) => $this->present($presenterKlass, $id), $ids),
                fn($item) => $item !== null,
            ),
        );

        // mark for clean up to filter out `null` values from the presentation
        // marker
        $result[Presenter::CLEAN_UP_ARRAY_MARKER] = true;

        return $result;
    }

    /**
     * Present a entity in array form from any field name
     *
     * Field name must be present in entity
     *
     * ```php
     * // Will create the `SomeUserPresenter` presenter and call it with the
     * // entity associated with the presenter and `role_id = $id`
     * 'userWithRole' => $this->presentInversedRef(
     *      SomeUserPresenter::class,
     *      'role_id',
     *      $roleId,
     * );
     * ```
     *
     * @psalm-template TEntityPresenter of EntityPresenter
     * @psalm-param class-string<TEntityPresenter> $presenterKlass
     *
     * @param string $presenterKlass Class to present the entity with
     * @param string $fieldName Name of referenced field
     * @param int|null $id ID of the entity
     * @param ClauseInterface|null $clause Optional clause to manipulate resulting entities
     * @return PresentationMarker|null Marker with presenter info
     */
    protected function presentInversedRef(
        string $presenterKlass,
        string $fieldName,
        ?int $id,
        ?ClauseInterface $clause = null
    ): ?PresentationMarker {
        Database::assertValidPresenterClass($presenterKlass);

        if ($id === null) {
            return null;
        }

        return new PresentationMarker($presenterKlass, $fieldName, $id, false, $clause);
    }

    /**
     * Present entity in array form through a relation entity
     *
     * Field names must be present in entity
     *
     * ```php
     * // Will create the `SomeRolePresenter` presenter and call it with the
     * // entity associated with the presenter and are available through the
     * // `User`-table with `id = $id` and `role_id = primary ID`
     * 'ownerRole' => $this->presentThroughInversedRef(
     *      User::class,
     *      'id',
     *      $userId,
     *      'role_id',
     *      SomeRolePresenter::class,
     * );
     * ```
     *
     * @psalm-template TEntityParam of Entity
     * @psalm-param class-string<TEntityParam> $relationEntityKlass
     * @psalm-template TEntityPresenter of EntityPresenter
     * @psalm-param class-string<TEntityPresenter> $targetPresenterKlass
     *
     * @param string $relationEntityKlass Entity class name
     * @param string $fromFieldName Name of referenced field for source
     * @param int|null $id ID of the entity
     * @param string $toFieldName Name of referenced field for target
     * @param string $targetPresenterKlass Class to present the entity with
     * @param ClauseInterface|null $relationClause Optional clause to manipulate resulting entities (applied to relation entities)
     * @return FutureMarker|null Marker with presenter info
     */
    protected function presentThroughInversedRef(
        string $relationEntityKlass,
        string $sourceFieldName,
        ?int $id,
        string $targetFieldName,
        string $targetPresenterKlass,
        ?ClauseInterface $relationClause = null
    ): ?FutureMarker {
        Database::assertValidEntityClass($relationEntityKlass);
        Database::assertValidPresenterClass($targetPresenterKlass);

        if ($id === null) {
            return null;
        }

        return $this->presentFutureInversedRef(
            $relationEntityKlass,
            $sourceFieldName,
            $id,
            fn(Entity $entity) => $this->present(
                $targetPresenterKlass,
                $this->getValidRefId($entity, $targetFieldName),
            ),
            $relationClause,
        );
    }

    /**
     * Present a entity in array form from any field name
     *
     * Field name must be present in entity
     *
     * ```php
     * // Will create the `SomeUserPresenter` presenter and call it with the
     * // entities associated with the presenter and `role_id = $id`
     * 'usersWithRole' => $this->presentMultipleInversedRefs(
     *      SomeUserPresenter::class,
     *      'role_id',
     *      $roleId,
     * );
     * ```
     *
     * @psalm-template TEntityPresenter of EntityPresenter
     * @psalm-param class-string<TEntityPresenter> $presenterKlass
     *
     * @param string $presenterKlass Class to present the entity with
     * @param string $fieldName Name of referenced field
     * @param int|null $id ID of the entity
     * @param ClauseInterface|null $clause Optional clause to manipulate resulting entities
     * @return PresentationMarker|array|null Marker with presenter info (or empty array on empty ID)
     */
    protected function presentMultipleInversedRefs(
        string $presenterKlass,
        string $fieldName,
        ?int $id,
        ?ClauseInterface $clause = null
    ) {
        Database::assertValidPresenterClass($presenterKlass);

        if ($id === null) {
            return [];
        }

        return new PresentationMarker($presenterKlass, $fieldName, $id, true, $clause);
    }

    /**
     * Present multiple entities in array form through a relation entity
     *
     * Field names must be present in entity
     *
     * ```php
     * // Will create the `SomeProjectPresenter` presenter and call it with the
     * // entities associated with the presenter and are available through the
     * // `ProjectUser`-table with `user_id = $id` and `project_id = primary ID`
     * 'projects' => $this->presentMultipleThroughInversedRefs(
     *      ProjectUser::class,
     *      'user_id',
     *      $userId,
     *      'project_id',
     *      SomeProjectPresenter::class,
     * );
     * ```
     *
     * @psalm-template TEntityParam of Entity
     * @psalm-param class-string<TEntityParam> $relationEntityKlass
     * @psalm-template TEntityPresenter of EntityPresenter
     * @psalm-param class-string<TEntityPresenter> $targetPresenterKlass
     *
     * @param string $relationEntityKlass Entity class name
     * @param string $fromFieldName Name of referenced field for source
     * @param int|null $id ID of the entity
     * @param string $toFieldName Name of referenced field for target
     * @param string $targetPresenterKlass Class to present the entity with
     * @param ClauseInterface|null $relationClause Optional clause to manipulate resulting entities (applied to relation entities)
     * @return FutureMarker|array|null Marker with presenter info (or empty array on empty ID)
     */
    protected function presentMultipleThroughInversedRefs(
        string $relationEntityKlass,
        string $sourceFieldName,
        ?int $id,
        string $targetFieldName,
        string $targetPresenterKlass,
        ?ClauseInterface $relationClause = null
    ) {
        Database::assertValidEntityClass($relationEntityKlass);
        Database::assertValidPresenterClass($targetPresenterKlass);

        if ($id === null) {
            return [];
        }

        return $this->presentFutureMultipleInversedRefs(
            $relationEntityKlass,
            $sourceFieldName,
            $id,
            fn(Collection $collection) => $this->presentMultiple(
                $targetPresenterKlass,
                array_filter(
                    $collection->map(
                        fn(Entity $entity) => $this->getValidRefId($entity, $targetFieldName),
                    ),
                ),
            ),
            $relationClause,
        );
    }

    /**
     * Create a future presentation for entity with ID
     *
     * Callback will be called when the future is resolved
     *
     * ```php
     * // Will fetch the `User` entity with primary ID `$userId` and call
     * // callback
     * 'userName' => $this->presentFuture(
     *      User::class,
     *      $userId,
     *      fn (User $user) => $user->getName(),
     * );
     * ```
     *
     * @psalm-template TEntityParam of Entity
     * @psalm-param class-string<TEntityParam> $entityKlass
     *
     * @param string $entityKlass Entity class name
     * @param int|null $id ID of the entity
     * @param \Closure $callback On resolved callback
     * @param ClauseInterface|null $clause Optional clause to manipulate resulting entity
     * @return FutureMarker|null Marker with future presentation info
     */
    protected function presentFuture(
        string $entityKlass,
        ?int $id,
        \Closure $callback,
        ?ClauseInterface $clause = null
    ): ?FutureMarker {
        Database::assertValidEntityClass($entityKlass);

        return $this->presentFutureInversedRef($entityKlass, 'id', $id, $callback, $clause);
    }

    /**
     * Create a future presentation for entity with field name
     *
     * Callback will be called when the future is resolved
     *
     * ```php
     * // Will fetch the `User` entity with `role_id = $roleId` and call
     * // callback
     * 'userName' => $this->presentFuture(
     *      User::class,
     *      'role_id',
     *      $roleId,
     *      fn (User $user) => $user->getName(),
     * );
     * ```
     *
     * @psalm-template TEntityParam of Entity
     * @psalm-param class-string<TEntityParam> $entityKlass
     *
     * @param string $entityKlass Entity class name
     * @param string $fieldName Name of referenced field
     * @param int|null $id ID of the entity
     * @param \Closure $callback On resolved callback
     * @param ClauseInterface|null $clause Optional clause to manipulate resulting entity
     * @return FutureMarker|null Marker with future presentation info
     */
    protected function presentFutureInversedRef(
        string $entityKlass,
        string $fieldName,
        ?int $id,
        \Closure $callback,
        ?ClauseInterface $clause = null
    ): ?FutureMarker {
        Database::assertValidEntityClass($entityKlass);

        if ($id === null) {
            return null;
        }

        return new FutureMarker($entityKlass, $fieldName, $id, false, $callback, $clause);
    }

    /**
     * Create a future presentation for multiple entities with field name
     *
     * Callback will be called when the future is resolved
     *
     * ```php
     * // Will fetch multiple `User` entities with `role_id = $roleId` and call
     * // callback
     * 'userName' => $this->presentFutureMultipleInversedRefs(
     *      User::class,
     *      'role_id',
     *      $roleId,
     *      fn (User $user) => $user->getName(),
     * );
     * ```
     *
     * @psalm-template TEntityParam of Entity
     * @psalm-param class-string<TEntityParam> $entityKlass
     *
     * @param string $entityKlass Entity class name
     * @param string $fieldName Name of referenced field
     * @param int|null $id ID of the entity
     * @param \Closure $callback On resolved callback
     * @param ClauseInterface|null $clause Optional clause to manipulate resulting entities
     * @return FutureMarker|array|null Marker with future presentation info (or empty array on empty ID)
     */
    protected function presentFutureMultipleInversedRefs(
        string $entityKlass,
        string $fieldName,
        ?int $id,
        \Closure $callback,
        ?ClauseInterface $clause = null
    ) {
        Database::assertValidEntityClass($entityKlass);

        if ($id === null) {
            return [];
        }

        return new FutureMarker($entityKlass, $fieldName, $id, true, $callback, $clause);
    }

    /**
     * Present a date
     *
     * Format: Y-m-d (2012-04-25)
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
     * Format: \DateTime::ATOM (2012-04-25T12:14:18+00:00)
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

    /**
     * Return a valid ref ID for a target field name of an entity
     */
    private function getValidRefId(Entity $entity, string $targetFieldName): ?int
    {
        $values = $entity->getValues();

        if (!isset($values[$targetFieldName])) {
            return null;
        }

        if (!is_int($values[$targetFieldName])) {
            return null;
        }

        return $values[$targetFieldName];
    }
}
