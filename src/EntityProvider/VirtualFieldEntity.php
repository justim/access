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
     */
    public function __construct(string $virtualFieldName, ?string $virtualType)
    {
        parent::__construct([
            $virtualFieldName => [
                'type' => $virtualType,
            ],
        ]);

        $this->virtualFieldName = $virtualFieldName;
    }

    /**
     * Get the result of the virtual field
     *
     * @return mixed
     */
    public function getVirtualField()
    {
        return $this->get($this->virtualFieldName);
    }
}
