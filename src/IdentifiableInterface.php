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

/**
 * Is an object identifiable?
 *
 * @author Tim <me@justim.net>
 */
interface IdentifiableInterface
{
    /**
     * Get the identifier of object
     *
     * @return mixed
     */
    public function getId();
}
