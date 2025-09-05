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

use Access\Cascade;
use Access\Entity;
use Access\Schema\Table;
use BackedEnum;

/**
 * Base entity class to fetch a virtual entity
 *
 * @author Tim <me@justim.net>
 *
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
abstract class VirtualEntity extends Entity
{
    /**
     * Fields used in this virtual entity
     *
     * @psalm-var array<string, FieldOptions>
     */
    private array $fields;

    /**
     * Create a virtual entity
     *
     * @param array $fields Fiels used in virtual entity
     * @psalm-param array<string, FieldOptions> $fields
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
     * Method is never executed due to overload of `getResolvedTableSchema` method
     * @codeCoverageIgnore
     *
     * @return array<string, mixed>
     * @psalm-return array<string, FieldOptions>
     */
    public static function fields(): array
    {
        return [];
    }

    /**
     * Return the resolved table schema based on virtual fields
     */
    protected function getResolvedTableSchema(): Table
    {
        return static::getGeneratedTableSchema($this->fields);
    }
}
