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

namespace Access\Migrations;

/**
 * Migration
 *
 * @author Tim <me@justim.net>
 */
abstract class Migration
{
    abstract public function constructive(SchemaChanges $schemaChanges): void;

    public function destructive(SchemaChanges $schemaChanges): void {}

    abstract public function revertConstructive(SchemaChanges $schemaChanges): void;

    public function revertDestructive(SchemaChanges $schemaChanges): void {}
}
