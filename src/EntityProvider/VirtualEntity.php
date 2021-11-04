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
 * Base entity class to fetch a virtual entity
 *
 * @author Tim <me@justim.net>
 */
abstract class VirtualEntity extends Entity
{
    /**
     * Fields used in this virtual entity
     *
     * @psalm-var array<string, array{default?: mixed, type?: string, virtual?: bool, excludeInCopy?: bool}>
     */
    private array $fields;

    /**
     * Create a virtual entity
     *
     * @param array $fields Fiels used in virtual entity
     */
    public function __construct(array $fields)
    {
        $this->fields = $fields;
    }

    /**
     * Return a dummy table name
     *
     * Method is never executed due to nature of this class, its all fake
     * @codeCoverageIgnore
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
     * Method is never executed due to overload of `getResolvedFields` method
     * @codeCoverageIgnore
     *
     * @return array<string, mixed>
     * @psalm-return array<string, array{default?: mixed, type?: string, virtual?: bool, excludeInCopy?: bool}>
     */
    public static function fields(): array
    {
        return [];
    }

    /**
     * Resolved table definition with virtual field info
     *
     * @return array<string, mixed>
     * @psalm-return array<string, array{default?: mixed, type?: string, virtual?: bool, excludeInCopy?: bool}>
     */
    protected function getResolvedFields(): array
    {
        return $this->fields;
    }
}
