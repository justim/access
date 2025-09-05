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

namespace Access\Migrations\Exception;

use Access\Exception;
use Access\Migrations\MigrationResult;
use Access\Migrations\SchemaChanges;

class MigrationFailedException extends Exception
{
    private MigrationResult $migrationResult;

    public function __construct(MigrationResult $migrationResult, \Throwable $previous)
    {
        $this->migrationResult = $migrationResult;

        parent::__construct(
            sprintf('%s: %s', $migrationResult->getMessage(), $previous->getMessage()),
            0,
            $previous,
        );
    }

    public function getChanges(): SchemaChanges
    {
        $changes = $this->migrationResult->getChanges();

        // the failure case always has schema changes
        assert($changes instanceof SchemaChanges);

        return $changes;
    }
}
