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

/**
 * Cascade delete kind
 *
 * What to do when a parent entity is deleted
 *
 * @author Tim <me@justim.net>
 */
enum CascadeDeleteKind
{
    /**
     * No cascade delete
     */
    case None;

    /**
     * Keep the same delete kind as the parent
     */
    case Same;

    /**
     * Force hard delete
     */
    case ForceRegular;
}
