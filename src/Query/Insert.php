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

namespace Access\Query;

use Access\Clause\Field;
use Access\Database;
use Access\Driver\DriverInterface;
use Access\Query;
use Access\Schema\Table;

/**
 * Create a INSERT query for given table
 *
 * @author Tim <me@justim.net>
 */
class Insert extends Query
{
    /**
     * @param Table|string $tableName Name of the table (or name of entity class)
     * @param string $alias Name of the alias for given table name
     */
    public function __construct(Table|string $tableName, ?string $alias = null)
    {
        parent::__construct($tableName, $alias);
    }

    /**
     * {@inheritdoc}
     */
    public function getSql(?DriverInterface $driver = null): ?string
    {
        $driver = Database::getDriverOrDefault($driver);

        $sqlInsert = 'INSERT INTO ' . $driver->escapeIdentifier($this->tableName);

        // escape field names
        $sqlFields = array_map(
            fn(string $field): string => $driver->escapeIdentifier($field),
            array_keys($this->values),
        );

        $sqlFields = ' (' . implode(', ', $sqlFields) . ')';

        // filter out `Field` instances and use name directly
        $sqlValues = array_map(
            fn(mixed $value): string => $value instanceof Field
                ? $driver->escapeIdentifier($value->getName())
                : '?',
            $this->values,
        );

        $sqlValues = ' VALUES (' . implode(', ', $sqlValues) . ')';

        $sql = $sqlInsert . $sqlFields . $sqlValues;

        return $this->replaceQuestionMarks($sql, self::PREFIX_PARAM);
    }
}
