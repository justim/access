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
use Access\Schema\Type\Reference;
use Access\Schema\Type\Text;

/**
 * Migration
 *
 * @author Tim <me@justim.net>
 */
class Version2025080412000 extends Migration
{
    public function constructive(SchemaChanges $schemaChanges): void
    {
        $users = $schemaChanges->createTable(
            'users',
            hasCreatedAt: true,
            hasUpdatedAt: true,
            hasDeletedAt: true,
        );

        $users->field('role', default: 'USER');

        $posts = $schemaChanges->createTable('posts');
        $posts->field('title');
        $posts->field('content', new Text());
        $userId = $posts->field('user_id', new Reference($users));
        $posts->index('user_id_index', $userId);

        $posts = $schemaChanges->alterTable('posts');
        $posts->addField('summary', new Text(), null);
    }

    public function destructive(SchemaChanges $schemaChanges): void
    {
        // let's imagine the posts table was created in some previous migration
        $posts = $schemaChanges->alterTable('posts');
        $posts->renameField('content', 'body');
    }

    public function revertConstructive(SchemaChanges $schemaChanges): void
    {
        $schemaChanges->dropTable('posts');
        $schemaChanges->dropTable('users');
    }

    public function revertDestructive(SchemaChanges $schemaChanges): void
    {
        $posts = $schemaChanges->alterTable('posts');
        $posts->renameField('body', 'content');
    }
}
