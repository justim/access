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

namespace Access\Migrations;

use Access\Entity;
use Access\Schema\Table;
use Access\Schema\Type;

/**
 * @psalm-suppress MixedReturnStatement
 */
class MigrationEntity extends Entity
{
    public static function tableName(): string
    {
        return 'access_migration_versions';
    }

    public static function fields(): array
    {
        return [];
    }

    public static function getTableSchema(): Table
    {
        $table = new Table(
            static::tableName(),
            hasCreatedAt: true,
            hasUpdatedAt: true,
            hasDeletedAt: true,
        );

        $version = $table->field('version');
        $versionIndex = $table->index('version_index', $version);
        $versionIndex->unique();

        $table->field('constructive_executed_at', new Type\DateTime(), null);
        $table->field('destructive_executed_at', new Type\DateTime(), null);
        $table->field('constructive_reverted_at', new Type\DateTime(), null);
        $table->field('destructive_reverted_at', new Type\DateTime(), null);

        return $table;
    }

    public function setVersion(string $version): void
    {
        $this->set('version', $version);
    }

    public function getVersion(): ?string
    {
        return $this->get('version');
    }

    public function setConstructiveExecutedAt(?\DateTimeImmutable $constructiveExecutedAt): void
    {
        $this->set('constructive_executed_at', $constructiveExecutedAt);
    }

    public function getConstructiveExecutedAt(): ?\DateTimeImmutable
    {
        return $this->get('constructive_executed_at');
    }

    public function setDestructiveExecutedAt(?\DateTimeImmutable $destructiveExecutedAt): void
    {
        $this->set('destructive_executed_at', $destructiveExecutedAt);
    }

    public function getDestructiveExecutedAt(): ?\DateTimeImmutable
    {
        return $this->get('destructive_executed_at');
    }

    public function setConstructiveRevertedAt(?\DateTimeImmutable $constructiveRevertedAt): void
    {
        $this->set('constructive_reverted_at', $constructiveRevertedAt);
    }

    public function getConstructiveRevertedAt(): ?\DateTimeImmutable
    {
        return $this->get('constructive_reverted_at');
    }

    public function setDestructiveRevertedAt(?\DateTimeImmutable $destructiveRevertedAt): void
    {
        $this->set('destructive_reverted_at', $destructiveRevertedAt);
    }

    public function getDestructiveRevertedAt(): ?\DateTimeImmutable
    {
        return $this->get('destructive_reverted_at');
    }
}
