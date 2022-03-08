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
use Access\Presenter\MarkerInterface;

/**
 * Future presenter marker
 *
 * @internal
 * @psalm-template TEntity of \Access\Entity
 * @author Tim <me@justim.net>
 */
final class FutureMarker implements MarkerInterface
{
    /**
     * Entity class to present with
     *
     * @psalm-var class-string<TEntity>
     */
    private string $entityKlass;

    /**
     * Name of the field for entity
     */
    private string $fieldName;

    /**
     * ID of the references field of entity
     *
     * @var int[]
     */
    private array $refIds;

    /**
     * Marker expects multiple entities when filled
     */
    private bool $multiple;

    /**
     * Function to call when future is resolved
     */
    private \Closure $callback;

    /**
     * Extra clause for matching/sorting purposes
     */
    private ?ClauseInterface $clause;

    /**
     * Create a marker with future presentation information
     *
     * @psalm-param class-string<TEntity> $entityKlass
     * @param string $entityKlass
     * @param int|int[] $refIds ID of the entity
     * @param bool $multiple Fill with multiple entities when filled
     * @param \Closure $callback Function to call when future is resolved
     */
    public function __construct(
        string $entityKlass,
        string $fieldName,
        int|array $refIds,
        bool $multiple,
        \Closure $callback,
        ClauseInterface $clause = null,
    ) {
        $this->entityKlass = $entityKlass;
        $this->fieldName = $fieldName;
        $this->refIds = is_array($refIds) ? $refIds : [$refIds];
        $this->multiple = $multiple;
        $this->callback = $callback;
        $this->clause = $clause;
    }

    /**
     * Get the entity class name
     *
     * @psalm-return class-string<TEntity>
     * @return string
     */
    public function getEntityKlass(): string
    {
        return $this->entityKlass;
    }

    /**
     * Get the referenced field name
     *
     * @return string
     */
    public function getFieldName(): string
    {
        return $this->fieldName;
    }

    /**
     * ID of the references field of entity
     *
     * @return int[]
     */
    public function getRefIds(): array
    {
        return $this->refIds;
    }

    /**
     * Marker expects multiple entities when filled
     *
     * @return bool
     */
    public function getMultiple(): bool
    {
        return $this->multiple;
    }

    /**
     * Function to call when future is resolved
     *
     * @return \Closure
     */
    public function getCallback(): \Closure
    {
        return $this->callback;
    }

    /**
     * Get (optional) extra clause for matching/sorting purposes
     *
     * @return ClauseInterface|null
     */
    public function getClause(): ?ClauseInterface
    {
        return $this->clause;
    }
}
