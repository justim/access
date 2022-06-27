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
    public function testTransactionCommit(): void
    {
        $db = self::createDatabase();

        $users = $db->findAll(User::class);
        $this->assertEquals(0, count(iterator_to_array($users)));

        $transaction = $db->beginTransaction();

        $dave = new User();
        $dave->setEmail('dave@example.com');
        $dave->setName('Dave');

        $db->insert($dave);

        $transaction->commit();

        // second time is a no-op
        $transaction->commit();

        $users = $db->findAll(User::class);
        $this->assertEquals(1, count(iterator_to_array($users)));
    }

    public function testTransactionRollBack(): void
    {
        $db = self::createDatabaseWithDummyData();

        $users = $db->findAll(User::class);
        $this->assertEquals(2, count(iterator_to_array($users)));

        $transaction = $db->beginTransaction();

        $dave = new User();
        $dave->setEmail('dave@example.com');
        $dave->setName('Dave');

        $db->insert($dave);

        $transaction->rollBack();

        // second time is a no-op
        $transaction->rollBack();

        $users = $db->findAll(User::class);
        $this->assertEquals(2, count(iterator_to_array($users)));
    }

    public function testTransactionUnfinished(): void
    {
        $db = self::createDatabaseWithDummyData();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Transaction still in progress');

        $transaction = $db->beginTransaction();
    }

    public function testNestedTransaction(): void
    {
        $db = self::createDatabase();

        $users = $db->findAll(User::class);
        $this->assertEquals(0, count(iterator_to_array($users)));

        $transactionOne = $db->beginTransaction();

        $dave = new User();
        $dave->setEmail('dave@example.com');
        $dave->setName('Dave');

        $db->insert($dave);

        $transactionTwo = $db->beginTransaction();

        $dave = new User();
        $dave->setEmail('dave@example.com');
        $dave->setName('Dave');

        $db->insert($dave);

        $transactionTwo->commit();

        $transactionOne->commit();

        $users = $db->findAll(User::class);
        $this->assertEquals(2, count(iterator_to_array($users)));
    }

    public function testNestedTransactionWithInnerRollback(): void
    {
        $db = self::createDatabase();

        $users = $db->findAll(User::class);
        $this->assertEquals(0, count(iterator_to_array($users)));

        $transactionOne = $db->beginTransaction();

        $dave = new User();
        $dave->setEmail('dave@example.com');
        $dave->setName('Dave');

        $db->insert($dave);

        $transactionTwo = $db->beginTransaction();

        $dave = new User();
        $dave->setEmail('dave@example.com');
        $dave->setName('Dave');

        $db->insert($dave);

        $transactionTwo->rollBack();

        $transactionOne->commit();

        $users = $db->findAll(User::class);
        $this->assertEquals(1, count(iterator_to_array($users)));
    }
}
