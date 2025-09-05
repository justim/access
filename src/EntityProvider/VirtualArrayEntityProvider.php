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

use Access\Entity;
use Access\Cascade;
use BackedEnum;

/**
 * Provide empty entity shells for virtual array use
 *
 * @author Tim <me@justim.net>
 *
 * @template-extends VirtualEntityProvider<VirtualArrayEntity>
 * @psalm-type FieldOptions = array{
 *  default?: mixed,
 *  type?: Entity::FIELD_TYPE_*,
 *  enumName?: class-string<BackedEnum>,
 *  virtual?: bool,
 *  excludeInCopy?: bool,
 *  target?: class-string<Entity>,
 *  cascade?: Cascade,
 * }
 */
class VirtualArrayEntityProvider extends VirtualEntityProvider
{
    /**
     * Fields used in this virtual entity
     *
     * @psalm-var array<string, FieldOptions>
     */
    private array $fields;

    /**
     * Create a virtual array entity provider
     *
     * @param array $fields Fiels used in virtual array entity
     * @psalm-param array<string, FieldOptions> $fields Fiels used in virtual array entity
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
