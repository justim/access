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

use Access\Schema\Type\VirtualMixedType;

/**
 * Entity class to fetch a single virtual field
 *
 * @author Tim <me@justim.net>
 */
class VirtualFieldEntity extends VirtualEntity
{
    /**
     * Name of the virtual field
     *
     * @var string
     */
    private string $virtualFieldName;

    /**
     * Create a virtual field entity
     *
     * @param string $virtualFieldName Name of the virtual field
     * @param string|null $virtualType Type of the virtual field
     * @psalm-param self::FIELD_TYPE_*|null $virtualType
     */
    public function __construct(string $virtualFieldName, ?string $virtualType)
    {
        $field = [];

        if ($virtualType !== null) {
            $field['type'] = $virtualType;
        } else {
            // the default for `null` type is `String`, but with the
            // introduction of the type classes the string type has
            // gotten a bit more strict, it expects values coming from
            // the database to be a string. the mixed type just allows
            // everything to be passed through, which is what we want
            // for a virtual field with no type
            $field['type'] = new VirtualMixedType();
        }

        parent::__construct([
            $virtualFieldName => $field,
        ]);

        $this->virtualFieldName = $virtualFieldName;
    }

    /**
     * Get the result of the virtual field
     *
     * @return mixed
     */
    public function getVirtualField(): mixed
    {
        return $this->get($this->virtualFieldName);
    }
}
