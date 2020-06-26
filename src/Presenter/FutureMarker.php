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

use Access\Presenter\MarkerInterface;

/**
 * Future presenter marker
 *
 * @internal
 * @psalm-template TEntity of Entity
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
     */
    private int $refId;

    /**
     * Marker expects multiple entities when filled
     */
    private bool $multiple;

    /**
     * Function to call when future is resolved
     */
    private \Closure $callback;

    /**
     * Create a marker with future presentation information
     *
     * @psalm-param class-string<TEntity> $entityKlass
     * @param string $entityKlass
     * @param int $refId ID of the entity
     * @param bool $multiple Fill with multiple entities when filled
     * @param \Closure $callback Function to call when future is resolved
     */
    public function __construct(
        string $entityKlass,
        string $fieldName,
        int $refId,
        bool $multiple,
        \Closure $callback
    ) {
        $this->entityKlass = $entityKlass;
        $this->fieldName = $fieldName;
        $this->refId = $refId;
        $this->multiple = $multiple;
        $this->callback = $callback;
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
     * @retun int
     */
    public function getRefId(): int
    {
        return $this->refId;
    }

    /**
     * Marker expects multiple entities when filled
     *
     * @retun int
     */
    public function getMultiple(): bool
    {
        return $this->multiple;
    }

    /**
     * Function to call when future is resolved
     *
     * @retun \Closure
     */
    public function getCallback(): \Closure
    {
        return $this->callback;
    }
}
