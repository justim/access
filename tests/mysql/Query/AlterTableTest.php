<?php

declare(strict_types=1);

namespace Tests\Mysql\Query;

use Access\Query\AlterTable;
use Access\Query\CreateTable;
use Access\Schema\Table;
use Access\Schema\Type;
use PHPUnit\Framework\TestCase;

use Tests\Base\DatabaseBuilderInterface;
use Tests\Fixtures\UserStatus;
use Tests\Mysql\DatabaseBuilderTrait;

class AlterTableTest extends TestCase implements DatabaseBuilderInterface
{
    use DatabaseBuilderTrait;

    public function testQuery(): void
    {
        $db = self::createEmptyDatabase();

        $users = new Table('users', hasCreatedAt: true, hasUpdatedAt: true, hasDeletedAt: true);
        $users->field('name', new Type\VarChar(50), 'Dave');
        $users->field('status', new Type\Enum(UserStatus::class));

        $query = new CreateTable($users);

        $this->assertEquals(
            <<<SQL
            CREATE TABLE `users` (
                `id` INT NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(50) NOT NULL DEFAULT "Dave",
                `status` ENUM("ACTIVE", "BANNED") NOT NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                `deleted_at` DATETIME NULL DEFAULT NULL,
                PRIMARY KEY (`id`)
            ) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ENGINE=InnoDB
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
            ALTER TABLE `users` ADD COLUMN `role` VARCHAR(30) NULL
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
            ALTER TABLE `users` DROP COLUMN `role`
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
            ALTER TABLE `users` RENAME COLUMN `name` TO `first_name`
            SQL
            ,
            $query->getSql($db->getDriver()),
        );

        $db->query($query);

        $users = new Table('users');
        $currentName = $users->field('first_name');
        $newName = $users->field('first_name', new Type\VarChar(50), 'John');

        $query = new AlterTable($users);
        $query->changeField($currentName, $newName);

        $this->assertEquals(
            <<<SQL
            ALTER TABLE `users` CHANGE COLUMN `first_name` `first_name` VARCHAR(50) NOT NULL DEFAULT "John"
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
            CREATE TABLE `users` (
                `id` INT NOT NULL AUTO_INCREMENT,
                PRIMARY KEY (`id`)
            ) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ENGINE=InnoDB
            SQL
            ,
            $query->getSql($db->getDriver()),
        );

        $db->query($query);

        $projects = new Table('projects');
        $projects->field('owner_id', new Type\Reference($users));

        $query = new CreateTable($projects);

        $this->assertEquals(
            <<<SQL
            CREATE TABLE `projects` (
                `id` INT NOT NULL AUTO_INCREMENT,
                `owner_id` INT NOT NULL,
                PRIMARY KEY (`id`),
                FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`)
            ) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ENGINE=InnoDB
            SQL
            ,
            $query->getSql($db->getDriver()),
        );

        $db->query($query);

        $projects = new Table('projects');
        $ownerId = $projects->field('owner_id', new Type\Reference($users));
        $ownerIndex = $projects->index('owner_id_index', $ownerId);

        $query = new AlterTable($projects);
        $query->addIndex($ownerIndex);

        $this->assertEquals(
            <<<SQL
            ALTER TABLE `projects` ADD INDEX `owner_id_index` (`owner_id`)
            SQL
            ,
            $query->getSql($db->getDriver()),
        );

        $query = new AlterTable($projects);
        $query->removeIndex($ownerIndex);

        $this->assertEquals(
            <<<SQL
            ALTER TABLE `projects` DROP INDEX `owner_id_index`
            SQL
            ,
            $query->getSql($db->getDriver()),
        );

        $query = new AlterTable($projects);
        $ownerIndex->unique();
        $query->addIndex($ownerIndex);

        $this->assertEquals(
            <<<SQL
            ALTER TABLE `projects` ADD UNIQUE INDEX `owner_id_index` (`owner_id`)
            SQL
            ,
            $query->getSql($db->getDriver()),
        );

        $newOwnerIndex = $projects->index('new_owner_id_index', $ownerId);

        $query = new AlterTable($projects);
        $query->renameIndex($ownerIndex, $newOwnerIndex);

        $this->assertEquals(
            <<<SQL
            ALTER TABLE `projects` RENAME INDEX `owner_id_index` TO `new_owner_id_index`
            SQL
            ,
            $query->getSql($db->getDriver()),
        );
    }

    public function testRenameTable(): void
    {
        $db = self::createEmptyDatabase();

        $users = new Table('users');

        $query = new CreateTable($users);

        $this->assertEquals(
            <<<SQL
            CREATE TABLE `users` (
                `id` INT NOT NULL AUTO_INCREMENT,
                PRIMARY KEY (`id`)
            ) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ENGINE=InnoDB
            SQL
            ,
            $query->getSql($db->getDriver()),
        );

        $db->query($query);

        $users = new AlterTable($users);
        $users->renameTable('members');

        $this->assertEquals(
            <<<SQL
            ALTER TABLE `users` RENAME TO `members`
            SQL
            ,
            $users->getSql($db->getDriver()),
        );

        $db->query($users);
    }
}
