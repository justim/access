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
 * Entity class to fetch a single virtual field
 *
 * @author Tim <me@justim.net>
 */
class VirtualFieldEntity extends Entity
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
     */
    private ?string $virtualType;

    /**
     * Create a virtual field entity
     *
     * @param string $virtualFieldName Name of the virtual field
     * @param string|null $virtualType Type of the virtual field
     */
    public function __construct(
        string $virtualFieldName,
        ?string $virtualType
    ) {
        $this->virtualFieldName = $virtualFieldName;
        $this->virtualType = $virtualType;
    }

    /**
     * Return a dummy table name
     *
     * @return string
     */
    public static function tableName(): string
    {
        return '__dummy__';
    }

    /**
     * Return a empty table definition
     *
     * @return array
     */
    public static function fields(): array
    {
        return [];
    }

    /**
     * Resolved table definition with virtual field info
     *
     * @return array<string, mixed>
     * @psalm-return array<string, array{default: mixed, type: string, virual: bool}>
     */
    protected function getResolvedFields(): array
    {
        return [
            $this->virtualFieldName => [
                'type' => $this->virtualType,
            ],
        ];
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
