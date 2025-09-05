<?php

declare(strict_types=1);

namespace Tests\Sqlite\Query;

use Access\Query\CreateTable;
use Access\Schema\Table;
use Access\Query\DropTable;
use PHPUnit\Framework\TestCase;

use Tests\Base\DatabaseBuilderInterface;
use Tests\Sqlite\DatabaseBuilderTrait;

class DropTableTest extends TestCase implements DatabaseBuilderInterface
{
    use DatabaseBuilderTrait;

    public function testQuery(): void
    {
        $db = self::createEmptyDatabase();

        $users = new Table('users');

        $query = new CreateTable($users);
        $db->query($query);

        $query = new DropTable($users);

        $this->assertEquals(
            <<<SQL
            DROP TABLE "users"
            SQL
            ,
            $query->getSql($db->getDriver()),
        );

        $db->query($query);
    }
}
