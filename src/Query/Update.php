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
    public function getSql(): ?string
    {
        $i = 0;
        $fields = implode(
            ', ',
            array_map(
                function ($q) use (&$i) {
                    $placeholder = self::PREFIX_PARAM . $i;
                    $i++;
                    return $this->escapeIdentifier($q) . ' = :' . $placeholder;
                },
                array_keys($this->values)
            )
        );

        // there are not updated fields, no query needs to be executed
        if (empty($fields)) {
            return null;
        }

        $sqlUpdate = 'UPDATE ' . $this->escapeIdentifier($this->tableName);
        $sqlAlias = $this->getAliasSql();
        $sqlFields = ' SET ' . $fields;
        $sqlWhere = $this->getWhereSql();
        $sqlLimit = $this->getLimitSql();

        return $sqlUpdate. $sqlAlias . $sqlFields . $sqlWhere . $sqlLimit;
    }
}
