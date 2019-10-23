<?php

declare(strict_types=1);

namespace Benchmarks;

use Access\Database;
use Access\Query\Raw;
use Tests\Fixtures\Entity\User;

/**
 * @Revs(1000)
 * @Iterations(10)
 * @BeforeMethods({"init"})
 */
class EntityBench
{
    private $db;

    public function init()
    {
        $this->db = Database::create('sqlite::memory:');

        $createUsersQuery = new Raw('CREATE TABLE `users` (
            `id` INTEGER PRIMARY KEY AUTOINCREMENT,
            `role` VARCHAR(20) DEFAULT NULL,
            `name` VARCHAR(50) DEFAULT NULL,
            `email` VARCHAR(150) DEFAULT NULL,
            `created_at` DATETIME,
            `updated_at` DATETIME
        )');

        $this->db->query($createUsersQuery);

        $dave = new User();
        $dave->setEmail('dave@example.com');
        $dave->setName('Dave');

        $this->db->insert($dave);
    }

    public function benchHydrate()
    {
        $user = $this->db->findOne(User::class, 1);
        $user->getCreatedAt();
    }
}
