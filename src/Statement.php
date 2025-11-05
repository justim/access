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

use Access\Query\Insert;
use Access\Query\Select;

/**
 * Query executer
 *
 * Keeps prepared statements open
 *
 * @author Tim <me@justim.net>
 */
final class Statement
{
    private Database $db;

    private StatementPool $statementPool;

    /**
     * The query to execute
     *
     * @var Query
     */
    private Query $query;

    /**
     * The SQL to execute
     */
    private ?string $sql;

    private ProfilerInterface $profiler;

    /**
     * Create a statement
     */
    public function __construct(Database $db, ProfilerInterface $profiler, Query $query)
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
     * @return \Generator - yields Entity for select queries
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
            throw new Exception('Unable to prepare query: ' . $e->getMessage(), 0, $e);
        } finally {
            $profile->endPrepare();
        }

        try {
            $profile->startExecute();
            $statement->execute($this->query->getValues($this->db->getDriver()));
        } catch (\PDOException $e) {
            throw new Exception('Unable to execute query: ' . $e->getMessage(), 0, $e);
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
                throw new Exception('Unable to fetch: ' . $e->getMessage(), 0, $e);
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
     */
    private function getReturnValue(): ?int
    {
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
    }
}
