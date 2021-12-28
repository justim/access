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

use Access\Entity;

/**
 * Provide empty entity shells
 *
 * @psalm-template TEntity of Entity
 * @author Tim <me@justim.net>
 */
class EntityProvider
{
    /**
     * @psalm-var class-string<TEntity> $klass
     *
     * @var string Entity class name
     */
    private string $klass;

    /**
     * Create a entity provider
     *
     * @psalm-param class-string<TEntity> $klass
     * @param string $klass Entity class name
     */
    public function __construct(string $klass)
    {
        $this->klass = $klass;
    }

    /**
     * Create a empty entity shell
     *
     * @psalm-return TEntity
     * @return Entity
     */
    public function create(): Entity
    {
        /** @psalm-suppress UnsafeInstantiation */
        return new $this->klass();
    }
}
