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

use Access\EntityProvider;
use Access\Exception;

/**
 * Provide empty entity shells for virtual use
 *
 * @author Tim <me@justim.net>
 */
abstract class VirtualEntityProvider extends EntityProvider
{
    public function __construct()
    {
        // parent expects a class, we don't have one here
    }

    /**
     * Create a virtual field entity
     *
     * @return VirtualFieldEntity
     */
    public function create(): VirtualEntity
    {
        throw new Exception('Virtual entity provider is missing a `create` implementation');
    }
}
