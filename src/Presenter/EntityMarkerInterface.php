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

/**
 * Presentation marker
 *
 * @internal
 * @psalm-template TEntity of \Access\Entity
 * @author Tim <me@justim.net>
 */
interface EntityMarkerInterface
{
    /**
     * Get the entity class name
     *
     * @psalm-return class-string<TEntity>
     * @return string
     */
    public function getEntityKlass(): string;

    /**
     * Get the referenced field name
     *
     * @return string
     */
    public function getFieldName(): string;

    /**
     * IDs of the references field of entity
     *
     * @return int[]
     */
    public function getRefIds(): array;

    /**
     * Marker expects multiple entities when filled
     *
     * @return bool
     */
    public function getMultiple(): bool;

    /**
     * Get (optional) extra clause for matching/sorting purposes
     *
     * @return ClauseInterface|null
     */
    public function getClause(): ?ClauseInterface;
}
