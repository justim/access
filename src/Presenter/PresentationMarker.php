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
 * Presentation marker
 *
 * @internal
 * @psalm-template TEntityPresenter of \Access\Presenter\EntityPresenter
 * @psalm-template TEntity of \Access\Entity
 * @author Tim <me@justim.net>
 */
final class PresentationMarker implements MarkerInterface
{
    /**
     * Presenter class to present with
     *
     * @psalm-var class-string<TEntityPresenter>
     */
    private string $presenterKlass;

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
     * Extra clause for matching/sorting purposes
     */
    private ?ClauseInterface $clause;

    /**
     * Create a marker with presentation information
     *
     * @psalm-param class-string<TEntityPresenter> $presenterKlass
     * @param string $presenterKlass Class to present the entity with
     * @param int $refId ID of the entity
     * @param bool $multiple Fill with multiple entities when filled
     */
    public function __construct(
        string $presenterKlass,
        string $fieldName,
        int $refId,
        bool $multiple,
        ClauseInterface $clause = null,
    ) {
        $this->presenterKlass = $presenterKlass;
        $this->fieldName = $fieldName;
        $this->refId = $refId;
        $this->multiple = $multiple;
        $this->clause = $clause;
    }

    /**
     * Get the presenter class
     *
     * @psalm-return class-string<TEntityPresenter>
     * @return string
     */
    public function getPresenterKlass(): string
    {
        return $this->presenterKlass;
    }

    /**
     * Get the entity class name
     *
     * @psalm-return class-string<TEntity>
     * @return string
     */
    public function getEntityKlass(): string
    {
        /** @psalm-var class-string<TEntity> $entityKlass */
        $entityKlass = $this->presenterKlass::getEntityKlass();

        return $entityKlass;
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
     * @return int
     */
    public function getRefId(): int
    {
        return $this->refId;
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
     * Get (optional) extra clause for matching/sorting purposes
     *
     * @return ClauseInterface|null
     */
    public function getClause(): ?ClauseInterface
    {
        return $this->clause;
    }
}
