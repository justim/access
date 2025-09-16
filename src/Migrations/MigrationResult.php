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

enum MigrationResultType
{
    case Success;
    case Failure;
    case ConstructiveNotExecuted;
    case BlockedByDestructiveChange;
    case DestructiveNotExecuted;
    case AlreadyExecuted;

    public function isSuccess(): bool
    {
        return $this === self::Success;
    }

    public function isFailre(): bool
    {
        return $this === self::Failure;
    }

    public function isWarning(): bool
    {
        return $this === self::AlreadyExecuted;
    }

    public function isError(): bool
    {
        return $this === self::ConstructiveNotExecuted ||
            $this === self::BlockedByDestructiveChange ||
            $this === self::DestructiveNotExecuted;
    }

    public function getMessage(): string
    {
        return match ($this) {
            self::Success => 'Migration executed successfully',
            self::Failure => 'Migration execution failed',
            self::ConstructiveNotExecuted => 'Constructive migration was not executed',
            self::BlockedByDestructiveChange => 'Blocked by destructive change',
            self::DestructiveNotExecuted => 'Destructive migration was not executed',
            self::AlreadyExecuted => 'Migration has already been executed',
        };
    }
}

class MigrationResult
{
    private ?SchemaChanges $changes;
    private MigrationResultType $type;
    private ?Checkpoint $checkpoint = null;

    private function __construct(
        ?SchemaChanges $changes,
        MigrationResultType $type,
        ?Checkpoint $checkpoint = null,
    ) {
        $this->changes = $changes;
        $this->type = $type;
        $this->checkpoint = $checkpoint;
    }

    public static function success(SchemaChanges $changes): self
    {
        return new self($changes, MigrationResultType::Success);
    }

    public static function failure(SchemaChanges $changes, Checkpoint $checkpoint): self
    {
        return new self($changes, MigrationResultType::Failure, $checkpoint);
    }

    public static function constructiveNotExecuted(): self
    {
        return new self(null, MigrationResultType::ConstructiveNotExecuted);
    }

    public static function blockedByDestructiveChange(): self
    {
        return new self(null, MigrationResultType::BlockedByDestructiveChange);
    }

    public static function destructiveNotExecuted(): self
    {
        return new self(null, MigrationResultType::DestructiveNotExecuted);
    }

    public static function alreadyExecuted(): self
    {
        return new self(null, MigrationResultType::AlreadyExecuted);
    }

    public function getChanges(): ?SchemaChanges
    {
        return $this->changes;
    }

    public function getCheckpoint(): ?Checkpoint
    {
        return $this->checkpoint;
    }

    public function isSuccess(): bool
    {
        return $this->type->isSuccess();
    }

    public function isWarning(): bool
    {
        return $this->type->isWarning();
    }

    public function isError(): bool
    {
        return $this->type->isError();
    }

    public function getMessage(): string
    {
        return $this->type->getMessage();
    }
}
