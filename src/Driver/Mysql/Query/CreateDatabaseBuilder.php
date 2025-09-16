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

namespace Access\Driver\Mysql\Query;

use Access\Driver\DriverInterface;
use Access\Driver\Query\CreateDatabaseBuilderInterface;
use Access\Schema;
use Access\Schema\Charset;
use Access\Schema\Collate;

/**
 * @see https://dev.mysql.com/doc/refman/8.4/en/create-database.html
 * @author Tim <me@justim.net>
 * @internal
 */
class CreateDatabaseBuilder implements CreateDatabaseBuilderInterface
{
    private DriverInterface $driver;

    public function __construct(DriverInterface $driver)
    {
        $this->driver = $driver;
    }

    public function createOptions(Schema $schema): string
    {
        $defaultCharset = match ($schema->getDefaultCharset()) {
            Charset::Utf8 => 'utf8mb4',
        };

        $collate = match ($schema->getDefaultCollate()) {
            Collate::Default => 'utf8mb4_general_ci',
        };

        return sprintf(
            'DEFAULT CHARACTER SET=%s DEFAULT COLLATE=%s',
            $this->driver->escapeIdentifier($defaultCharset),
            $this->driver->escapeIdentifier($collate),
        );
    }
}
