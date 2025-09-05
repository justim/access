<?php

declare(strict_types=1);

namespace Tests\Sqlite\Query;

use Access\Query\AlterTable;
use Access\Query\CreateTable;
use Access\Schema\Table;
use Access\Schema\Type;
use PHPUnit\Framework\TestCase;

use Tests\Base\DatabaseBuilderInterface;
use Tests\Fixtures\UserStatus;
use Tests\Sqlite\DatabaseBuilderTrait;

class AlterTableTest extends TestCase implements DatabaseBuilderInterface
{
    use DatabaseBuilderTrait;

    public function testQuery(): void
    {
        $db = self::createEmptyDatabase();

        $users = new Table('users', hasCreatedAt: true, hasUpdatedAt: true, hasDeletedAt: true);

        $users->field('name', new Type\VarChar(50), 'Dave');

        $users->field('status', new Type\Enum(UserStatus::class), UserStatus::ACTIVE);

        $query = new CreateTable($users);

        $this->assertEquals(
            <<<SQL
            CREATE TABLE "users" (
                "id" INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
                "name" VARCHAR(50) NOT NULL DEFAULT 'Dave',
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

        $users = new Table('users');

        $users->field('name', new Type\VarChar(50), 'Dave');

        $query = new AlterTable($users);

        $role = $users->field('role', new Type\VarChar(30));
        $role->markAsNullable();
        $query->addField($role);

        $this->assertEquals(
            <<<SQL
            ALTER TABLE "users" ADD COLUMN "role" VARCHAR(30) NULL
            SQL
            ,
            $query->getSql($db->getDriver()),
        );

        $db->query($query);

        $query = new AlterTable($users);
        $role = $users->field('role');
        $query->removeField($role);

        $this->assertEquals(
            <<<SQL
            ALTER TABLE "users" DROP COLUMN "role"
            SQL
            ,
            $query->getSql($db->getDriver()),
        );

        $db->query($query);

        $users = new Table('users');
        $currentName = $users->field('name');
        $newName = $users->field('first_name');

        $query = new AlterTable($users);
        $query->renameField($currentName, $newName);

        $this->assertEquals(
            <<<SQL
            ALTER TABLE "users" RENAME COLUMN "name" TO "first_name"
            SQL
            ,
            $query->getSql($db->getDriver()),
        );

        $db->query($query);
    }
}
