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

use Access\Database;
use Access\Exception;

/**
 * Transaction
 *
 * @author Tim <me@justim.net>
 */
class Transaction
{
    /**
     * @var Database
     */
    private $db;

    /**
     * Is the transaction currently in motion
     *
     * @var bool $inTransaction
     */
    private $inTransaction = false;

    /**
     * Create a transaction
     *
     * @param Database $db
     */
    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Make sure we commit/rollback transactions
     */
    public function __destruct()
    {
        if ($this->inTransaction) {
            throw new Exception('Transaction still in progress');
        }
    }

    /**
     * Begin transaction
     */
    public function begin(): void
    {
        $this->db->getConnection()->beginTransaction();
        $this->inTransaction = true;
    }

    /**
     * Commit all changes in transaction
     */
    public function commit(): void
    {
        if (!$this->inTransaction) {
            return;
        }

        $this->db->getConnection()->commit();
        $this->inTransaction = false;
    }

    /**
     * Roll back all changes in transaction
     */
    public function rollBack(): void
    {
        if (!$this->inTransaction) {
            return;
        }

        $this->db->getConnection()->rollBack();
        $this->inTransaction = false;
    }
}
