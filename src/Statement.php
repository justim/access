<?php

declare(strict_types=1);

namespace Access;

use Access\Profiler;
use Access\Query\Insert;
use Access\Query\Select;

/**
 * Query executer
 *
 * Keeps prepared statements open
 */
final class Statement
{
    /**
     * All open prepared statements
     *
     * @var \PDOStatement[]
     */
    private static $stmtPool = [];

    /**
     * Database connection
     *
     * @var \PDO
     */
    private $connection = null;

    /**
     * Is the query prepared
     *
     * @var bool
     */
    private $isPrepared = false;

    /**
     * The prepared statement
     *
     * @var \PDOStatement
     */
    private $statement = null;

    /**
     * The query to execute
     *
     * @var Query
     */
    private $query = null;

    /**
     * The SQL to execute
     *
     * @var string|null
     */
    private $sql = null;

    /**
     * Profiler
     *
     * @var Profiler $profiler
     */
    private $profiler = null;

    /**
     * Create a statement
     *
     * @param Database $db
     * @param Query $query
     */
    public function __construct(Database $db, Profiler $profiler, Query $query)
    {
        $this->connection = $db->getConnection();
        $this->query = $query;
        $this->profiler = $profiler;

        // cache the sql
        $this->sql = $query->getSql();

        $this->prepare();
    }

    /**
     * Close prepared statement
     */
    public function close(): void
    {
        $this->isPrepared = false;

        unset(self::$stmtPool[$this->sql]);
    }

    /**
     * Execute the query
     *
     * @return \Generator - yields Entity for select queries
     */
    public function execute(): \Generator
    {
        $profile = $this->profiler->createForQuery($this->query);

        if ($this->sql === null) {
            return $this->getReturnValue();
        }

        $profile->startPrepare();
        if (!$this->isPrepared) {
            $this->prepare();
        }
        $profile->endPrepare();

        $profile->startExecute();
        $this->statement->execute($this->query->getValues());
        $profile->endExecute();

        if ($this->query instanceof Select) {
            while ($row = $this->statement->fetch(\PDO::FETCH_ASSOC)) {
                yield $row;
            }
        }

        return $this->getReturnValue();
    }

    /**
     * Prepare the query
     *
     * Reuse prepared statement if we have one open
     */
    private function prepare(): void
    {
        if ($this->sql === null) {
            return;
        }

        if (!isset(self::$stmtPool[$this->sql])) {
            self::$stmtPool[$this->sql] = $this->connection->prepare($this->sql);
        }

        $this->statement = self::$stmtPool[$this->sql];
        $this->isPrepared = true;
    }

    /**
     * Get the return value based in query type
     *
     * @return ?int
     */
    private function getReturnValue(): ?int
    {
        if ($this->query instanceof Insert) {
            if ($this->sql === null) {
                return -1;
            }

            return (int) $this->connection->lastInsertId();
        }

        if (!$this->query instanceof Select) {
            if ($this->sql === null) {
                return 0;
            }

            return $this->statement->rowCount();
        }

        return null;
    }
}
