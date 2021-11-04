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

use Access\Exception;

/**
 * Entity class to fetch a couple of fields with array access
 *
 * This virtual entity is immutable
 *
 * @author Tim <me@justim.net>
 */
class VirtualArrayEntity extends VirtualEntity implements \ArrayAccess
{
    /**
     * Does the field exist?
     *
     * @param string $field
     * @return bool
     */
    public function offsetExists($field)
    {
        return $this->hasValue($field);
    }

    /**
     * Get the value of a field
     *
     * @param string $field
     * @return mixed
     */
    public function offsetGet($field)
    {
        return $this->get($field);
    }

    /**
     * Not allowed
     *
     * @throws Exception
     */
    public function offsetSet($id, $value)
    {
        throw new Exception('Not possible to update virtual array entities');
    }

    /**
     * Not allowed
     *
     * @throws Exception
     */
    public function offsetUnset($id)
    {
        throw new Exception('Not possible to update virtual array entities');
    }
}
