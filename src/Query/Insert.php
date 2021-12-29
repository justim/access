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
 * Create a INSERT query for given table
 *
 * @author Tim <me@justim.net>
 */
class Insert extends Query
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
        $sqlInsert = 'INSERT INTO ' . self::escapeIdentifier($this->tableName);
        $sqlFields = ' (' . implode(', ', array_keys($this->values)) . ')';
        $sqlValues = ' VALUES (' . implode(', ', array_fill(0, count($this->values), '?')) . ')';

        $sql = $sqlInsert . $sqlFields . $sqlValues;

        return $this->replaceQuestionMarks($sql, self::PREFIX_PARAM);
    }
}
