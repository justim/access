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

use Access\Exception\NotSupportedException;
use Access\Query\Select;

/**
 * Create a UNION query for given SELECT queries
 *
 * @author Tim <me@justim.net>
 */
class Union extends Select
{
    /** @var string */
    private const PREFIX_UNION = 'u';

    /** @var Select[] */
    private array $queries;

    public function __construct(Select $query, Select ...$queries)
    {
        $this->queries = [$query, ...$queries];
        parent::__construct('__dummy__');
    }

    /**
     * Add a SELECT query to the UNION
     *
     * @param Select $query
     */
    public function addQuery(Select $query): void
    {
        $this->queries[] = $query;
    }

    /**
     * Not supported for UNION queries
     *
     * @param string|null $select
     */
    public function select($select): never
    {
        throw new NotSupportedException('Not supported for UNION queries');
    }

    /**
     * Not supported for UNION queries
     *
     * @param string $fieldName Name of the field
     * @param string $fieldValue Value of the field in SQL
     */
    public function addVirtualField(string $fieldName, string $fieldValue): never
    {
        throw new NotSupportedException('Not supported for UNION queries');
    }

    /**
     * Get the SQL for the query
     *
     * This includes all SELECT queries, UNIONed together. Plus, ORDER BY and LIMIT
     */
    public function getSql(): string
    {
        $unions = [];
        $i = 0;

        foreach ($this->queries as $query) {
            $oldIncludeSoftDeleted = $query->setIncludeSoftDeleted($this->includeSoftDeleted);

            $unions[] = preg_replace(
                '/:(([a-z]+[a-z0-9]*))/',
                ':' . self::PREFIX_UNION . $i . '$1',
                (string) $query->getSql(),
            );

            $i++;

            $query->setIncludeSoftDeleted($oldIncludeSoftDeleted);
        }

        $sqlUnion = implode(' UNION ', $unions);
        $sqlOrderBy = $this->getOrderBySql();
        $sqlLimit = $this->getLimitSql();

        if ($sqlLimit !== '' || $sqlOrderBy !== '') {
            // only wrap the UNIONs in parentheses if there is an ORDER BY or LIMIT
            $sqlUnion = "($sqlUnion)";
        }

        return $sqlUnion . $sqlOrderBy . $sqlLimit;
    }

    /**
     * Get the values with a prefixed index
     *
     * @return array<string, mixed> The values
     */
    public function getValues(): array
    {
        /** @var array<string, mixed> $values */
        $values = [];

        $i = 0;

        foreach ($this->queries as $query) {
            $oldIncludeSoftDeleted = $query->setIncludeSoftDeleted($this->includeSoftDeleted);

            /** @var mixed $nestedValue */
            foreach ($query->getValues() as $nestedIndex => $nestedValue) {
                $doubleNestedIndex = self::PREFIX_UNION . $i . $nestedIndex;
                /** @psalm-suppress MixedAssignment */
                $values[$doubleNestedIndex] = $nestedValue;
            }

            $i++;

            $query->setIncludeSoftDeleted($oldIncludeSoftDeleted);
        }

        return $values;
    }
}
