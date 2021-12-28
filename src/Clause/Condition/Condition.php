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

namespace Access\Clause\Condition;

use Access\Clause\ConditionInterface;
use Access\Entity;

/**
 * Condition clause
 *
 * @author Tim <me@justim.net>
 */
abstract class Condition implements ConditionInterface
{
    protected const KIND_EQUALS = '=';
    protected const KIND_NOT_EQUALS = '!=';
    protected const KIND_GREATER_THAN = '>';
    protected const KIND_GREATER_THAN_OR_EQUALS = '>=';
    protected const KIND_LESS_THAN = '<';
    protected const KIND_LESS_THAN_OR_EQUALS = '<=';

    /**
     * Name of the field to compare
     */
    private string $fieldName;

    /**
     * What kind of condition
     */
    private string $kind;

    /**
     * Value to compare to
     *
     * @var mixed
     */
    private mixed $value;

    /**
     * Create condition for given field name and value
     *
     * @param string $fieldName
     * @param string $kind
     * @param mixed $value
     */
    protected function __construct(string $fieldName, string $kind, mixed $value)
    {
        $this->fieldName = $fieldName;
        $this->kind = $kind;
        $this->value = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function matchesEntity(?Entity $entity): bool
    {
        if ($entity === null) {
            return false;
        }

        if ($this->fieldName === 'id') {
            $value = $entity->getId();
        } else {
            $values = $entity->getValues();

            if (!array_key_exists($this->fieldName, $values)) {
                return false;
            }

            /** @var mixed $value */
            $value = $values[$this->fieldName];
        }

        switch ($this->kind) {
            case self::KIND_EQUALS:
                return $value === $this->value;

            case self::KIND_NOT_EQUALS:
                return $value !== $this->value;

            case self::KIND_GREATER_THAN:
                return $value > $this->value;

            case self::KIND_GREATER_THAN_OR_EQUALS:
                return $value >= $this->value;

            case self::KIND_LESS_THAN:
                return $value < $this->value;

            case self::KIND_LESS_THAN_OR_EQUALS:
                return $value <= $this->value;

            default:
                return false;
        }
    }
}
