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

namespace Access\Driver\Mysql;

use Access\Driver\DriverInterface;
use Access\Driver\SqlTypeDefinitionBuilder;
use Access\Schema\Field;
use Access\Schema\Type;

/**
 * Base driver
 *
 * @author Tim <me@justim.net>
 * @internal
 */
class MysqlSqlTypeDefinitionBuilder extends SqlTypeDefinitionBuilder
{
    private DriverInterface $driver;

    public function __construct(DriverInterface $driver)
    {
        $this->driver = $driver;
    }

    public function fromField(Field $field): string
    {
        $innerType = $this->fromType($field->getType());

        $parts = [$innerType];

        if ($field->isNullable()) {
            $parts[] = 'NULL';
        } else {
            $parts[] = 'NOT NULL';
        }

        if ($field->hasStaticDefault()) {
            $default = $field->getStaticDefaultValue();

            if ($default === null) {
                $parts[] = 'DEFAULT NULL';
            } else {
                $parts[] =
                    'DEFAULT ' .
                    $this->driver->getDebugSqlValue(
                        $field->getType()->toDatabaseFormatValue($default),
                    );
            }
        }

        if ($field->hasAutoIncrement()) {
            $parts[] = 'AUTO_INCREMENT';
        }

        return implode(' ', $parts);
    }

    public function fromBooleanType(Type\Boolean $type): string
    {
        return 'INT';
    }

    public function fromDateType(Type\Date $type): string
    {
        return 'DATE';
    }

    public function fromDateTimeType(Type\DateTime $type): string
    {
        return 'DATETIME';
    }

    public function fromEnumType(Type\Enum $type): string
    {
        return 'ENUM(' .
            implode(
                ', ',
                array_map(
                    fn(string|int $case): string => $this->driver->getDebugSqlValue($case),
                    $type->getCases(),
                ),
            ) .
            ')';
    }

    public function fromIntegerType(Type\Integer $type): string
    {
        return 'INT';
    }

    public function fromFloatType(Type\FloatType $type): string
    {
        return 'REAL';
    }

    public function fromVarCharType(Type\VarChar $type): string
    {
        return 'VARCHAR(' . $type->getSize() . ')';
    }

    public function fromTextType(Type\Text $type): string
    {
        return 'TEXT';
    }

    public function fromJsonType(Type\Json $type): string
    {
        return 'JSON';
    }

    public function fromReferenceType(Type\Reference $type): string
    {
        return 'INT';
    }
}
