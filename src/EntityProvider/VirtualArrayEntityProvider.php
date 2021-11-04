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

namespace Access\EntityProvider;

/**
 * Provide empty entity shells for virtual array use
 *
 * @author Tim <me@justim.net>
 */
class VirtualArrayEntityProvider extends VirtualEntityProvider
{
    /**
     * Fields used in this virtual entity
     *
     * @psalm-var array<string, array{default?: mixed, type?: string, virtual?: bool, excludeInCopy?: bool}>
     */
    private array $fields;

    /**
     * Create a virtual array entity provider
     *
     * @param array $fields Fiels used in virtual array entity
     */
    public function __construct(array $fields)
    {
        $this->fields = $fields;
    }

    /**
     * Create a virtual array entity
     *
     * @return VirtualArrayEntity
     */
    public function create(): VirtualArrayEntity
    {
        return new VirtualArrayEntity($this->fields);
    }
}
