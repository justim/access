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

use Access\Profiler;
use Access\Query;
use Access\Query\Insert;
use Access\Query\Select;
use Access\StatementPool;

/**
 * Query executer
 *
 * Keeps prepared statements open
 *
 * @author Tim <me@justim.net>
 */
final class Statement
{
    /**
     * Database
     *
     * @var Database
     */
    private Database $db;

    /**
     * @var StatementPool $statementPool
     */
    private StatementPool $statementPool;

    /**
     * The query to execute
     *
     * @var Query
     */
    private Query $query;

    /**
     * The SQL to execute
     *
     * @var string|null
     */
    private ?string $sql;

    /**
     * Profiler
     *
     * @var Profiler $profiler
     */
    private Profiler $profiler;

    /**
     * Create a statement
     *
     * @param Database $db
     * @param Profiler $profiler
     * @param Query $query
     */
    public function __construct(Database $db, Profiler $profiler, Query $query)
    {
        $this->db = $db;
        $this->statementPool = $db->getStatementPool();
        $this->query = $query;
        $this->profiler = $profiler;

        // cache the sql
        $this->sql = $query->getSql($db->getDriver());
    }

    /**
     * Execute the query
     *
     * @return \Generator<array<string, mixed>> - yields Entity for select queries
     */
    public function execute(): \Generator
    {
        $profile = $this->profiler->createForQuery($this->query);

        if ($this->sql === null) {
            return $this->getReturnValue();
        }

        try {
            $profile->startPrepare();
            $statement = $this->statementPool->prepare($this->sql);
        } catch (\PDOException $e) {
            throw $this->db->convertPdoException(
                $e,
                'Unable to prepare query: ' . $e->getMessage(),
            );
        } finally {
            $profile->endPrepare();
        }

        try {
            $profile->startExecute();
            $statement->execute($this->query->getValues($this->db->getDriver()));
        } catch (\PDOException $e) {
            throw $this->db->convertPdoException(
                $e,
                'Unable to execute query: ' . $e->getMessage(),
            );
        } finally {
            $profile->endExecute();
        }

        if ($this->query instanceof Select) {
            $numberOfResults = 0;

            try {
                $profile->startHydrate();

                while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
                    $numberOfResults++;
                    yield $row;
                }
            } catch (\PDOException $e) {
                throw $this->db->convertPdoException($e, 'Unable to fetch: ' . $e->getMessage());
            } finally {
                $profile->endHydrate();
                $profile->setNumberOfResults($numberOfResults);
            }
        } else {
            $profile->setNumberOfResults($statement->rowCount());
        }

        return $this->getReturnValue();
    }

    /**
     * Get the return value based in query type
     *
     * @return ?int
     */
    private function getReturnValue(): ?int
    {
        try {
            if ($this->query instanceof Insert) {
                if ($this->sql === null) {
                    // insert queries always return a string, but the type
                    // of this property is string|null, so we need to check
                    // for it
                    // @codeCoverageIgnoreStart
                    return -1;
                    // @codeCoverageIgnoreEnd
                }

                return (int) $this->db->getConnection()->lastInsertId();
            }

            if (!$this->query instanceof Select) {
                if ($this->sql === null) {
                    return 0;
                }

                $statement = $this->statementPool->prepare($this->sql);
                return $statement->rowCount();
            }

            return null;
        } catch (\PDOException $e) {
            throw $this->db->convertPdoException(
                $e,
                'Unable to get return value: ' . $e->getMessage(),
            );
        }
    }
}
