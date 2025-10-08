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

namespace Access;

/**
 * Row level locking types
 *
 * @internal
 * @see https://dev.mysql.com/doc/refman/8.4/en/innodb-locking-reads.html
 */
enum ReadLock
{
    /**
     * Sets a shared mode lock on any rows that are read. Other sessions can read the rows,
     * but cannot modify them until your transaction commits. If any of these rows were
     * changed by another transaction that has not yet committed, your query waits until
     * that transaction ends and then uses the latest values.
     */
    case Share;

    /**
     * For index records the search encounters, locks the rows and any associated index
     * entries, the same as if you issued an UPDATE statement for those rows. Other
     * transactions are blocked from updating those rows, from doing SELECT ... FOR SHARE,
     * or from reading the data in certain transaction isolation levels. Consistent reads
     * ignore any locks set on the records that exist in the read view. (Old versions of
     * a record cannot be locked; they are reconstructed by applying undo logs on an
     * in-memory copy of the record.)
     */
    case Update;

    /**
     * A locking read that uses NOWAIT never waits to acquire a row lock. The query
     * executes immediately, failing with an error if a requested row is locked.
     *
     * @see ReadLock::Share
     */
    case ShareNoWait;

    /**
     * A locking read that uses NOWAIT never waits to acquire a row lock. The query
     * executes immediately, failing with an error if a requested row is locked.
     *
     * @see ReadLock::Update
     */
    case UpdateNoWait;
}
