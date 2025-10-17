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
use Access\Query\Commit;
use Access\Query\Rollback;
use Access\Query\RollbackToSavepoint;
use Access\Query\Savepoint;
use Access\Query\Begin;

/**
 * Abstraction to handle database transactions
 *
 * Will create savepoints on nested transactions
 *
 * @author Tim <me@justim.net>
 */
class Transaction
{
    /**
     * @var Database
     */
    private Database $db;

    /**
     * Is the transaction currently in motion
     *
     * @var bool $inTransaction
     */
    private bool $inTransaction = false;

    /**
     * Current save point for nested transactions
     *
     * @var string|null $savepoint
     */
    private ?string $savepointIdentifier = null;

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
        try {
            $connection = $this->db->getConnection();

            if ($connection->inTransaction()) {
                $this->savepointIdentifier = $this->generateSavepointIdentifier();
                $query = new Savepoint($this->savepointIdentifier);
                $this->db->query($query);
            } else {
                $query = new Begin();
                $this->db->getProfiler()->createForQuery($query);
                $connection->beginTransaction();
            }

            $this->inTransaction = true;
        } catch (\PDOException $e) {
            throw $this->db->convertPdoException(
                $e,
                'Unable to begin transaction: ' . $e->getMessage(),
            );
        }
    }

    /**
     * Commit all changes in transaction
     */
    public function commit(): void
    {
        try {
            if (!$this->inTransaction) {
                return;
            }

            if ($this->savepointIdentifier !== null) {
                // the outer transaction will provide the commit
                $this->inTransaction = false;
                return;
            }

            $this->db->getConnection()->commit();

            $query = new Commit();
            $this->db->getProfiler()->createForQuery($query);

            $this->inTransaction = false;
        } catch (\PDOException $e) {
            throw $this->db->convertPdoException(
                $e,
                'Unable to commit transaction: ' . $e->getMessage(),
            );
        }
    }

    /**
     * Roll back all changes in transaction
     */
    public function rollBack(): void
    {
        try {
            if (!$this->inTransaction) {
                return;
            }

            if ($this->savepointIdentifier !== null) {
                $query = new RollbackToSavepoint($this->savepointIdentifier);
                $this->db->query($query);

                $this->inTransaction = false;
                return;
            }

            $this->db->getConnection()->rollBack();

            $query = new Rollback();
            $this->db->getProfiler()->createForQuery($query);

            $this->inTransaction = false;
        } catch (\PDOException $e) {
            throw $this->db->convertPdoException(
                $e,
                'Unable to roll back transaction: ' . $e->getMessage(),
            );
        }
    }

    private function generateSavepointIdentifier(): string
    {
        // same amount of random as a UUIDv4
        return sprintf('access_%s', bin2hex(random_bytes(16)));
    }
}
