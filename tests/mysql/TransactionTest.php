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

namespace Tests\Mysql;

use Access\Exception\LockNotAcquiredException;
use Access\Query\Raw;
use Access\Query\Select;
use Access\Query\Update;
use Access\Transaction;
use ReflectionProperty;
use Tests\Base\BaseTransactionTest;
use Tests\Fixtures\Entity\User;

class TransactionTest extends BaseTransactionTest
{
    use DatabaseBuilderTrait;

    public function testForShare(): void
    {
        $db1 = static::createDatabaseWithDummyData($name);
        $db2 = static::getFreshDatabaseConnection($name);

        $user1 = null;
        $user2 = null;
        $users = $db1->findAll(User::class);

        foreach ($users as $user) {
            if ($user1 === null) {
                $user1 = $user;
            } elseif ($user2 === null) {
                $user2 = $user;
            }
        }

        assert($user1 instanceof User);
        assert($user2 instanceof User);

        $transaction = $db1->beginTransaction();

        $select1 = new Select(User::class);
        $select1->where('id = ?', $user1->getId());
        $select1->readLockForShareNoWait();

        $select2 = new Select(User::class);
        $select2->where('id = ?', $user2->getId());
        $select2->readLockForShareNoWait();

        $db1->selectOne(User::class, $select1);
        $db2->selectOne(User::class, $select2);

        // record with ID 1 is locked by $db1, but it's only `FOR SHARE`, $db2 still has access
        $db2->selectOne(User::class, $select1);

        $update1 = new Update(User::class);
        $update1->values(['name' => 'Something different']);
        $update1->where('id = ?', $user1->getId());

        // not ideal to wait for a second, but better than waiting longer :)
        $db2->query(new Raw('SET innodb_lock_wait_timeout = 1'));

        try {
            $this->expectException(LockNotAcquiredException::class);
            $this->expectExceptionMessageMatches('/timeout exceeded/');

            // record with ID 1 is share locked by $db1, updating is not possible
            $db2->query($update1);

            $transaction->commit();
        } finally {
            // make sure don't keep a lock around
            $transaction->rollBack();
        }
    }

    public function testForUpdate(): void
    {
        $db1 = static::createDatabaseWithDummyData($name);
        $db2 = static::getFreshDatabaseConnection($name);

        $user1 = null;
        $user2 = null;
        $users = $db1->findAll(User::class);

        foreach ($users as $user) {
            if ($user1 === null) {
                $user1 = $user;
            } elseif ($user2 === null) {
                $user2 = $user;
            }
        }

        assert($user1 instanceof User);
        assert($user2 instanceof User);

        $transaction = $db1->beginTransaction();

        $select1 = new Select(User::class);
        $select1->where('id = ?', $user1->getId());
        $select1->readLockForUpdateNoWait();

        $select2 = new Select(User::class);
        $select2->where('id = ?', $user2->getId());
        $select2->readLockForUpdateNoWait();

        $db1->selectOne(User::class, $select1);
        $db2->selectOne(User::class, $select2);

        $this->expectException(LockNotAcquiredException::class);
        $this->expectExceptionMessageMatches('/NOWAIT/');

        try {
            // record with ID 1 is locked by $db1
            $db2->selectOne(User::class, $select1);
        } finally {
            // make sure don't keep a lock around
            $transaction->rollBack();
        }
    }
}
