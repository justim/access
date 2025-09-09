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

use Access\Clause\Condition\Raw;
use Access\Clause\Field;
use Access\Database;
use Access\Driver\DriverInterface;
use Access\Query;
use Access\Schema\Table;

/**
 * Create a UPDATE query for given table
 *
 * @author Tim <me@justim.net>
 */
class Update extends Query
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

        $parts = [];

        $i = 0;
        /** @var mixed $value */
        foreach ($this->values as $q => $value) {
            if ($value instanceof Field) {
                // use the name of the field directly
                $parts[] =
                    $driver->escapeIdentifier($q) .
                    ' = ' .
                    $driver->escapeIdentifier($value->getName());
            } elseif ($value instanceof Raw) {
                // use the raw part directly
                $parts[] = $driver->escapeIdentifier($q) . ' = ' . $value->getCondition();
            } else {
                $placeholder = self::PREFIX_PARAM . (string) $i;

                $parts[] = $driver->escapeIdentifier($q) . ' = :' . $placeholder;

                $i++;
            }
        }

        $fields = implode(', ', $parts);

        // there are not updated fields, no query needs to be executed
        if (empty($fields)) {
            return null;
        }

        $sqlUpdate = 'UPDATE ' . $driver->escapeIdentifier($this->tableName);
        $sqlAlias = $this->getAliasSql($driver);
        $sqlJoins = $this->getJoinSql($driver);
        $sqlFields = ' SET ' . $fields;
        $sqlWhere = $this->getWhereSql($driver);
        $sqlLimit = $this->getLimitSql();

        return $sqlUpdate . $sqlAlias . $sqlJoins . $sqlFields . $sqlWhere . $sqlLimit;
    }
}
