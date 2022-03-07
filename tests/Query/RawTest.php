<?php

declare(strict_types=1);

namespace Tests\Query;

use PHPUnit\Framework\TestCase;

use Access\Query\Raw;

class RawTest extends TestCase
{
    public function testQuery(): void
    {
        $query = new Raw('SHOW FULL PROCESSLIST');

        $this->assertEquals('SHOW FULL PROCESSLIST', $query->getSql());
    }

    public function testQueryValues(): void
    {
        $query = new Raw('SELECT * FROM users WHERE name = :name', ['name' => 'Dave']);

        $this->assertEquals('SELECT * FROM users WHERE name = :name', $query->getSql());
        $this->assertEquals(['name' => 'Dave'], $query->getValues());
    }
}
