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

use Access\Schema\Field;
use Access\Schema\Type;

/**
 * Driver specific sql type definition interface
 *
 * @author Tim <me@justim.net>
 * @internal
 */
interface SqlTypeDefinitionBuilderInterface
{
    public function fromField(Field $field): string;

    public function fromBooleanType(Type\Boolean $type): string;
    public function fromDateType(Type\Date $type): string;
    public function fromDateTimeType(Type\DateTime $type): string;
    public function fromEnumType(Type\Enum $type): string;
    public function fromIntegerType(Type\Integer $type): string;
    public function fromFloatType(Type\FloatType $type): string;
    public function fromVarCharType(Type\VarChar $type): string;
    public function fromVarBinaryType(Type\VarBinary $type): string;
    public function fromTextType(Type\Text $type): string;
    public function fromJsonType(Type\Json $type): string;
    public function fromReferenceType(Type\Reference $type): string;
}
