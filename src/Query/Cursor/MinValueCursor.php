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

namespace Access\Query\Cursor;

use Access\Clause\Condition\LessThan;
use Access\Query;

/**
 * Cursor that uses an offset for a specific field
 *
 * The offset is the minimum value of the field that is currently available for the client/user
 *
 * Make sure the order of the records is consistently descending between queries
 *
 * @author Tim <me@justim.net>
 */
class MinValueCursor extends OffsetCursor
{
    /**
     * {@inheritdoc}
     */
    public function apply(Query $query): void
    {
        /** @var mixed $offset */
        $offset = $this->getOffset();

        if ($offset !== null) {
            $tableName = $query->getRawResolvedTableName();
            $field = clone $this->getField();
            $field->maybeAddTableName($tableName);

            $condition = new LessThan($field, $this->getOffset());
            $query->where($condition);
        }

        $query->limit($this->pageSize);
    }
}
