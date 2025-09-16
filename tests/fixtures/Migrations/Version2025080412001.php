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

namespace Tests\Fixtures\Migrations;

use Access\Migrations\Migration;
use Access\Migrations\SchemaChanges;
use Access\Query\Insert;
use Access\Schema\Type;

/**
 * Migration
 *
 * @author Tim <me@justim.net>
 */
class Version2025080412001 extends Migration
{
    public function constructive(SchemaChanges $schemaChanges): void
    {
        $users = $schemaChanges->createTable(
            'users',
            hasCreatedAt: true,
            hasUpdatedAt: true,
            hasDeletedAt: true,
        );

        $users->field('roles', new Type\Json());

        $admin = new Insert($users);
        $admin->values([
            'roles' => json_encode(['ROLE_ADMIN']),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $schemaChanges->query($admin);
    }

    public function revertConstructive(SchemaChanges $schemaChanges): void
    {
        $schemaChanges->dropTable('users');
    }
}
