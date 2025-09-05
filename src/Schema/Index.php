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

namespace Access\Schema;

use Access\Clause\Field;
use Access\Driver\DriverInterface;

class Index
{
    private string $name;

    /** @var array<string|Field> */
    private array $fields;

    private bool $isUnique = false;

    /**
     * @param array<string|Field>|string|Field $fields
     */
    public function __construct(string $name, array|string|Field $fields)
    {
        $this->name = $name;

        if (!is_array($fields)) {
            $fields = [$fields];
        }

        $this->fields = $fields;
    }

    /**
     * Set whether the index is unique
     */
    public function unique(bool $isUnique = true): self
    {
        $this->isUnique = $isUnique;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array<string|Field>
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    public function isUnique(): bool
    {
        return $this->isUnique;
    }

    public function getSqlDefinition(DriverInterface $driver): string
    {
        return $driver->getSqlIndexDefinition($this);
    }
}
