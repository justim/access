<?php

declare(strict_types=1);

namespace Tests\Sqlite\Query;

use Access\Query\CreateTable;
use Access\Schema\Table;
use Access\Schema\Type;
use PHPUnit\Framework\TestCase;
use Tests\Base\DatabaseBuilderInterface;
use Tests\Fixtures\UserStatus;
use Tests\Sqlite\DatabaseBuilderTrait;

class CreateTableTest extends TestCase implements DatabaseBuilderInterface
{
    use DatabaseBuilderTrait;

    public function testQuery(): void
    {
        $db = self::createEmptyDatabase();

        $users = new Table('users', hasCreatedAt: true, hasUpdatedAt: true, hasDeletedAt: true);

        $users->field('name', new Type\VarChar(50), 'Dave');

        $role = $users->field('role', new Type\VarChar(30));
        $role->markAsNullable();

        $users->field('status', new Type\Enum(UserStatus::class), UserStatus::ACTIVE);

        $query = new CreateTable($users);

        $this->assertEquals(
            <<<SQL
            CREATE TABLE "users" (
                "id" INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
                "name" VARCHAR(50) NOT NULL DEFAULT 'Dave',
                "role" VARCHAR(30) NULL,
                "status" TEXT NOT NULL DEFAULT 'ACTIVE',
                "created_at" DATETIME NOT NULL,
                "updated_at" DATETIME NOT NULL,
                "deleted_at" DATETIME NULL DEFAULT NULL
            )
            SQL
            ,
            $query->getSql($db->getDriver()),
        );

        $db->query($query);

        $projects = new Table(
            'projects',
            hasCreatedAt: true,
            hasUpdatedAt: true,
            hasDeletedAt: true,
        );

        $projects->field('owner_id', new Type\Reference($users));

        $projects->field('name', new Type\VarChar(50), null);

        $query = new CreateTable($projects);

        $this->assertEquals(
            <<<SQL
            CREATE TABLE "projects" (
                "id" INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
                "owner_id" INTEGER NOT NULL,
                "name" VARCHAR(50) NULL DEFAULT NULL,
                "created_at" DATETIME NOT NULL,
                "updated_at" DATETIME NOT NULL,
                "deleted_at" DATETIME NULL DEFAULT NULL,
                FOREIGN KEY ("owner_id") REFERENCES "users" ("id")
            )
            SQL
            ,
            $query->getSql($db->getDriver()),
        );

        $db->query($query);
    }

    public function testIndex(): void
    {
        $db = self::createEmptyDatabase();

        $users = new Table('users');

        $query = new CreateTable($users);

        $this->assertEquals(
            <<<SQL
            CREATE TABLE "users" (
                "id" INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT
            )
            SQL
            ,
            $query->getSql($db->getDriver()),
        );

        $db->query($query);

        $projects = new Table('projects');
        $ownerId = $projects->field('owner_id', new Type\Reference($users));
        $projects->index('owner_id_index', $ownerId)->unique();

        $query = new CreateTable($projects);

        $this->assertEquals(
            <<<SQL
            CREATE TABLE "projects" (
                "id" INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
                "owner_id" INTEGER NOT NULL,
                FOREIGN KEY ("owner_id") REFERENCES "users" ("id"),
                UNIQUE ("owner_id")
            )
            SQL
            ,
            $query->getSql($db->getDriver()),
        );

        $db->query($query);
    }
}
