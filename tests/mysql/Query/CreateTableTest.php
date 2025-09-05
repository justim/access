<?php

declare(strict_types=1);

namespace Tests\Mysql\Query;

use Access\Query\CreateTable;
use Access\Schema\Table;
use Access\Schema\Type;
use PHPUnit\Framework\TestCase;

use Tests\Base\DatabaseBuilderInterface;
use Tests\Fixtures\UserStatus;
use Tests\Mysql\DatabaseBuilderTrait;

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

        $users->field('status', new Type\Enum(UserStatus::class));

        $query = new CreateTable($users);

        $this->assertEquals(
            <<<SQL
            CREATE TABLE `users` (
                `id` INT NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(50) NOT NULL DEFAULT "Dave",
                `role` VARCHAR(30) NULL,
                `status` ENUM("ACTIVE", "BANNED") NOT NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                `deleted_at` DATETIME NULL DEFAULT NULL,
                PRIMARY KEY (`id`)
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
            CREATE TABLE `projects` (
                `id` INT NOT NULL AUTO_INCREMENT,
                `owner_id` INT NOT NULL,
                `name` VARCHAR(50) NULL DEFAULT NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                `deleted_at` DATETIME NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`)
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
            CREATE TABLE `users` (
                `id` INT NOT NULL AUTO_INCREMENT,
                PRIMARY KEY (`id`)
            )
            SQL
            ,
            $query->getSql($db->getDriver()),
        );

        $db->query($query);

        $projects = new Table('projects');
        $ownerId = $projects->field('owner_id', new Type\Reference($users));
        $projects->index('owner_id_index', $ownerId);

        $query = new CreateTable($projects);

        $this->assertEquals(
            <<<SQL
            CREATE TABLE `projects` (
                `id` INT NOT NULL AUTO_INCREMENT,
                `owner_id` INT NOT NULL,
                PRIMARY KEY (`id`),
                FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`),
                INDEX `owner_id_index` (`owner_id`)
            )
            SQL
            ,
            $query->getSql($db->getDriver()),
        );

        $db->query($query);
    }
}
