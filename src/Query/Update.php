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
        $parts = [];

        foreach (array_keys($this->values) as $i => $q) {
            $placeholder = self::PREFIX_PARAM . (string) $i;
            $parts[] = $this->escapeIdentifier($q) . ' = :' . $placeholder;
        }

        $fields = implode(', ', $parts);

        // there are not updated fields, no query needs to be executed
        if (empty($fields)) {
            return null;
        }

        $sqlUpdate = 'UPDATE ' . $this->escapeIdentifier($this->tableName);
        $sqlAlias = $this->getAliasSql();
        $sqlJoins = $this->getJoinSql();
        $sqlFields = ' SET ' . $fields;
        $sqlWhere = $this->getWhereSql();
        $sqlLimit = $this->getLimitSql();

        return $sqlUpdate . $sqlAlias . $sqlJoins . $sqlFields . $sqlWhere . $sqlLimit;
    }
}
