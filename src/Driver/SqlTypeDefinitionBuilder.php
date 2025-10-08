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

namespace Access\Driver;

use Access\Exception;
use Access\Schema\Type;

/**
 * Base driver
 *
 * @author Tim <me@justim.net>
 * @internal
 */
abstract class SqlTypeDefinitionBuilder implements SqlTypeDefinitionBuilderInterface
{
    public function fromType(Type $type): string
    {
        return match ($type::class) {
            Type\Boolean::class => $this->fromBooleanType($type),
            Type\Date::class => $this->fromDateType($type),
            Type\DateTime::class => $this->fromDateTimeType($type),
            Type\Enum::class => $this->fromEnumType($type),
            Type\Integer::class => $this->fromIntegerType($type),
            Type\FloatType::class => $this->fromFloatType($type),
            Type\VarChar::class => $this->fromVarCharType($type),
            Type\VarBinary::class => $this->fromVarBinaryType($type),
            Type\Text::class => $this->fromTextType($type),
            Type\Json::class => $this->fromJsonType($type),
            Type\Reference::class => $this->fromReferenceType($type),
            default => throw new Exception('Unsupported type: ' . $type::class),
        };
    }
}
