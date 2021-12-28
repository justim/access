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

/**
 * Query generator state class to track some numbers/values
 *
 * @internal
 *
 * @author Tim <me@justim.net>
 */
class QueryGeneratorState
{
    /**
     * Tracked indexed values
     *
     * @var array $indexedValues
     * @psalm-var array<string, mixed> $indexedValues
     */
    private array $indexedValues = [];

    /**
     * Prefix used for the condition index
     */
    private string $conditionPrefix;

    /**
     * Prefix used for the subquery conditions index
     */
    private string $subQueryConditionPrefix;

    /**
     * Current condition index
     */
    private int $conditionIndex = 0;

    /**
     * Current subquery index
     */
    private int $subQueryIndex = 0;

    /**
     * Create the query generator state
     *
     * @param string $conditionPrefix Prefix used for the condition index
     * @param string $subQueryConditionPrefix Prefix used for the subquery conditions index
     */
    public function __construct(string $conditionPrefix, string $subQueryConditionPrefix)
    {
        $this->conditionPrefix = $conditionPrefix;
        $this->subQueryConditionPrefix = $subQueryConditionPrefix;
    }

    /**
     * Get the tracked indexed values
     *
     * @return array
     * @psalm-return array<string, mixed>
     */
    public function getIndexedValues(): array
    {
        return $this->indexedValues;
    }

    /**
     * Add a condition value to the tracked values
     *
     * @param mixed $value
     */
    public function addConditionValue($value): void
    {
        $index = $this->conditionPrefix . $this->conditionIndex;
        $this->indexedValues[$index] = $value;
        $this->conditionIndex++;
    }

    /**
     * Add the values of a subquery to the tracked values
     *
     * @param Select $subQuery The subquery with values to be tracked
     */
    public function addSubQueryValues(Select $subQuery): void
    {
        /** @var mixed $nestedValue */
        foreach ($subQuery->getValues() as $nestedIndex => $nestedValue) {
            $index = $this->getSubQueryIndexPrefix() . $nestedIndex;
            $this->indexedValues[$index] = $nestedValue;
        }

        $this->incrementSubQueryIndex();
    }

    /**
     * Get the subquery condition prefix
     */
    public function getSubQueryIndexPrefix(): string
    {
        return $this->subQueryConditionPrefix . $this->subQueryIndex;
    }

    /**
     * Increment the subquery index
     */
    public function incrementSubQueryIndex(): void
    {
        $this->subQueryIndex++;
    }
}
