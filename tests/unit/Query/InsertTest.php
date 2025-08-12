<?php

declare(strict_types=1);

namespace Tests\Unit\Query;

use Access\Clause\Field;
use Access\Query\Insert;
use PHPUnit\Framework\TestCase;

use Tests\Fixtures\Entity\User;

class InsertTest extends TestCase
{
    public function testField(): void
    {
        $query = new Insert(User::class);
        $query->values([
            'name' => new Field('email'),
        ]);

        $this->assertEquals('INSERT INTO `users` (`name`) VALUES (`email`)', $query->getSql());
        $this->assertEquals([], $query->getValues());
    }
}
