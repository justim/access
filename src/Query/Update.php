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
use Access\Driver\DriverInterface;
use Access\Query;

/**
 * Create a UPDATE query for given table
 *
 * @author Tim <me@justim.net>
 */
class Update extends Query
{
    /**
     * @param string $tableName Name of the table (or name of entity class)
     * @param string $alias Name of the alias for given table name
     */
    public function __construct(string $tableName, string $alias = null)
    {
        parent::__construct($tableName, $alias);
    }

    /**
     * {@inheritdoc}
     */
    public function getSql(?DriverInterface $driver = null): ?string
    {
        $driver = $this->getDriver($driver);

        $parts = [];

        $i = 0;
        /** @var mixed $value */
        foreach ($this->values as $q => $value) {
            if ($value instanceof Field) {
                // use the name of the field directly
                $parts[] =
                    self::escapeIdentifier($q) . ' = ' . self::escapeIdentifier($value->getName());
            } else {
                $placeholder = self::PREFIX_PARAM . (string) $i;

                $parts[] = self::escapeIdentifier($q) . ' = :' . $placeholder;

                $i++;
            }
        }

        $fields = implode(', ', $parts);

        // there are not updated fields, no query needs to be executed
        if (empty($fields)) {
            return null;
        }

        $sqlUpdate = 'UPDATE ' . self::escapeIdentifier($this->tableName);
        $sqlAlias = $this->getAliasSql();
        $sqlJoins = $this->getJoinSql($driver);
        $sqlFields = ' SET ' . $fields;
        $sqlWhere = $this->getWhereSql($driver);
        $sqlLimit = $this->getLimitSql();

        return $sqlUpdate . $sqlAlias . $sqlJoins . $sqlFields . $sqlWhere . $sqlLimit;
    }
}
