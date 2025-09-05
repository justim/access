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

namespace Tests\Fixtures\Entity;

use Access\Cascade;
use Tests\Fixtures\Repository\ProjectRepository;

use Access\Entity;
use Access\Schema\Field;
use Access\Schema\Table;
use Access\Schema\Type;

/**
 * SAFETY Return types are not known, they are stored in an array config
 * @psalm-suppress MixedReturnStatement
 * @psalm-suppress MixedInferredReturnType
 */
class Project extends Entity
{
    public static function getRepository(): string
    {
        return ProjectRepository::class;
    }

    public static function tableName(): string
    {
        return 'projects';
    }

    public static function fields(): array
    {
        return [
            'status' => [
                'default' => fn() => 'IN_PROGRESS',
            ],
            'name' => [],
            'owner_id' => [
                'type' => self::FIELD_TYPE_INT,
                'target' => User::class,
                'cascade' => Cascade::deleteSame(),
            ],
            'published_at' => [
                'default' => fn() => null,
                'type' => self::FIELD_TYPE_DATE,
                'excludeInCopy' => true,
            ],
            'user_name' => [
                'virtual' => true,
            ],
        ];
    }

    public static function getParentTableSchema(): Table
    {
        return parent::getTableSchema();
    }

    public static function getTableSchema(): Table
    {
        $table = new Table('projects', hasCreatedAt: true, hasUpdatedAt: true);

        $status = new Field('status');
        $status->setDefault(fn() => 'IN_PROGRESS');
        $table->addField($status);

        $name = new Field('name');
        $table->addField($name);

        $ownerId = new Field('owner_id', new Type\Reference(User::class, Cascade::deleteSame()));
        $table->addField($ownerId);

        $publishedAt = new Field('published_at', new Type\Date(), fn() => null);
        $publishedAt->setIncludeInCopy(false);
        $table->addField($publishedAt);

        $userName = new Field('user_name');
        $userName->markAsVirtual();
        $table->addField($userName);

        return $table;
    }

    public static function timestamps(): bool
    {
        return true;
    }

    public function getStatus(): string
    {
        return $this->get('status');
    }

    public function setOwnerId(User|int $ownerId): void
    {
        $this->set('owner_id', $ownerId);
    }

    public function getOwnerId(): int
    {
        return $this->get('owner_id');
    }

    public function setName(string $name): void
    {
        $this->set('name', $name);
    }

    public function getName(): string
    {
        return $this->get('name');
    }

    public function getUserName(): string
    {
        // will throw an exception when the field was not selected with virtual field
        return $this->get('user_name');
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->get(Table::CREATED_AT_FIELD);
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->get(Table::UPDATED_AT_FIELD);
    }

    public function getPublishedAt(): ?\DateTimeImmutable
    {
        return $this->get('published_at');
    }

    public function setPublishedAt(?\DateTimeInterface $publishedAt): void
    {
        $this->set('published_at', $publishedAt);
    }
}
