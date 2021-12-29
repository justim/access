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
 * Create DELETE query for given table
 *
 * @author Tim <me@justim.net>
 */
class Delete extends Query
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
        $sqlDeleteFrom = 'DELETE FROM ';

        if ($this->alias !== null) {
            $escapedAlias = self::escapeIdentifier($this->alias);
            $sqlDeleteFrom = "DELETE {$escapedAlias} FROM ";
        }

        $sqlDelete = $sqlDeleteFrom . self::escapeIdentifier($this->tableName);
        $sqlAlias = $this->getAliasSql();
        $sqlJoins = $this->getJoinSql();
        $sqlWhere = $this->getWhereSql();
        $sqlLimit = $this->getLimitSql();

        return $sqlDelete . $sqlAlias . $sqlJoins . $sqlWhere . $sqlLimit;
    }
}
