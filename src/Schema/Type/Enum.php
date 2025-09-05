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

namespace Access\Schema\Type;

use Access\Schema\Exception\InvalidDatabaseValueException;
use Access\Schema\Exception\InvalidFieldDefinitionException;
use Access\Schema\Type;
use BackedEnum;
use ValueError;

/**
 * @psalm-template T of BackedEnum
 */
class Enum extends Type
{
    /**
     * @var class-string<BackedEnum> $enumName
     * @psalm-var class-string<T> $enumName
     */
    private string $enumName;

    /**
     * @param class-string<BackedEnum> $enumName
     * @psalm-param class-string<T> $enumName
     */
    public function __construct(string $enumName)
    {
        /**
         * Users lie
         * @psalm-suppress DocblockTypeContradiction
         */
        if (empty($enumName)) {
            throw new InvalidFieldDefinitionException('Missing enum name');
        }

        if (!is_subclass_of($enumName, BackedEnum::class)) {
            throw new InvalidFieldDefinitionException(sprintf('Invalid enum name: %s', $enumName));
        }

        /**
         * The `is_subclass_of` check ensures this is correct, but Psalm does not detect it properly
         * @psalm-suppress PropertyTypeCoercion
         */
        $this->enumName = $enumName;
    }

    /**
     * @return array<string|int>
     */
    public function getCases(): array
    {
        return array_map(
            fn(BackedEnum $case): string|int => $case->value,
            $this->enumName::cases(),
        );
    }

    /**
     * @psalm-return T
     */
    public function fromDatabaseFormatValue(mixed $value): BackedEnum
    {
        if (!is_int($value) && !is_string($value)) {
            throw new InvalidDatabaseValueException('Invalid backing value for enum');
        }

        try {
            return $this->enumName::from($value);
        } catch (ValueError $e) {
            throw new InvalidDatabaseValueException('Invalid enum value', $e->getCode(), $e);
        }
    }

    public function toDatabaseFormatValue(mixed $value): string
    {
        if ($value instanceof BackedEnum) {
            return (string) $value->value;
        }

        return (string) $value;
    }
}
