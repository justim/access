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

/**
 * Provide empty entity shells for virtual field use
 *
 * @author Tim <me@justim.net>
 *
 * @template-extends VirtualEntityProvider<VirtualFieldEntity>
 */
class VirtualFieldEntityProvider extends VirtualEntityProvider
{
    /**
     * Name of the virtual field
     *
     * @var string
     */
    private string $virtualFieldName;

    /**
     * Optional type of the virtual field
     *
     * @var string|null
     * @psalm-var Entity::FIELD_TYPE_*|null
     */
    private ?string $virtualType;

    /**
     * Create a virtual field entity provider
     *
     * @param string $virtualFieldName Name of the virtual field
     * @param string|null $virtualType Type of the virtual field
     * @psalm-param Entity::FIELD_TYPE_*|null $virtualType
     */
    public function __construct(string $virtualFieldName, ?string $virtualType)
    {
        $this->virtualFieldName = $virtualFieldName;
        $this->virtualType = $virtualType;

        parent::__construct();
    }

    /**
     * Create a virtual field entity
     *
     * @return VirtualFieldEntity
     */
    public function create(): VirtualFieldEntity
    {
        return new VirtualFieldEntity($this->virtualFieldName, $this->virtualType);
    }
}
