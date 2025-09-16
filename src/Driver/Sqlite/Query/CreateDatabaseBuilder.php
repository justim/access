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

namespace Access\Driver\Sqlite\Query;

use Access\Driver\Query\CreateDatabaseBuilderInterface;
use Access\Exception\NotSupportedException;
use Access\Schema;

/**
 * @author Tim <me@justim.net>
 * @internal
 */
class CreateDatabaseBuilder implements CreateDatabaseBuilderInterface
{
    public function createOptions(Schema $schema): string
    {
        throw new NotSupportedException('SQLite does not support CREATE DATABASE statement.');
    }
}
