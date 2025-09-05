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
use Access\Schema\Type;

class DateTime extends Type
{
    public const DATABASE_FORMAT = 'Y-m-d H:i:s';

    public function __construct() {}

    public function fromDatabaseFormatValue(mixed $value): \DateTimeImmutable
    {
        if ($value instanceof \DateTimeInterface) {
            return $this->fromMutable($value);
        }

        if (!is_string($value)) {
            throw new InvalidDatabaseValueException('Invalid date time type: ' . gettype($value));
        }

        $result = \DateTimeImmutable::createFromFormat(
            self::DATABASE_FORMAT,
            $value,
            new \DateTimeZone('UTC'),
        );

        if ($result === false) {
            throw new InvalidDatabaseValueException('Invalid date time value: ' . $value);
        }

        return $result;
    }

    public function toDatabaseFormatValue(mixed $value): string
    {
        /** @var \DateTimeInterface $value */
        return \DateTimeImmutable::createFromInterface($value)
            ->setTimezone(new \DateTimeZone('UTC'))
            ->format(self::DATABASE_FORMAT);
    }

    /**
     * Make mutable date immutable, if needed
     *
     * @param \DateTimeInterface $date
     * @return \DateTimeImmutable
     */
    private function fromMutable(\DateTimeInterface $date): \DateTimeImmutable
    {
        if ($date instanceof \DateTimeImmutable) {
            return $date;
        }

        return new \DateTimeImmutable($date->format('Y-m-d H:i:s.u'), $date->getTimezone());
    }
}
