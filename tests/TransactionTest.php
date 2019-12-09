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

namespace Tests;

use Access\Exception;
use Tests\AbstractBaseTestCase;
use Tests\Fixtures\Entity\User;

class TransactionTest extends AbstractBaseTestCase
{
    public function testInsert(): void
    {
        // override test insert, we dont need it here
        $this->assertTrue(true);
    }

    public function testTransactionCommit(): void
    {
        $users = self::$db->findAll(User::class);
        $this->assertEquals(0, count(iterator_to_array($users)));

        $transaction = self::$db->beginTransaction();

        $dave = new User();
        $dave->setEmail('dave@example.com');
        $dave->setName('Dave');

        self::$db->insert($dave);

        $transaction->commit();

        // second time is a no-op
        $transaction->commit();

        $users = self::$db->findAll(User::class);
        $this->assertEquals(1, count(iterator_to_array($users)));
    }

    /**
     * @depends testTransactionCommit
     */
    public function testTransactionRollBack(): void
    {
        $users = self::$db->findAll(User::class);
        $this->assertEquals(1, count(iterator_to_array($users)));

        $transaction = self::$db->beginTransaction();

        $dave = new User();
        $dave->setEmail('dave@example.com');
        $dave->setName('Dave');

        self::$db->insert($dave);

        $transaction->rollBack();

        // second time is a no-op
        $transaction->rollBack();

        $users = self::$db->findAll(User::class);
        $this->assertEquals(1, count(iterator_to_array($users)));
    }

    public function testTransactionUnfinished(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Transaction still in progress');

        $transaction = self::$db->beginTransaction();
    }
}
