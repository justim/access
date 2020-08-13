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

use Tests\Fixtures\Repository\ProjectRepository;

use Access\Entity;

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
                'default' => function () {
                    return 'IN_PROGRESS';
                },
            ],
            'name' => [],
            'owner_id' => [
                'type' => self::FIELD_TYPE_INT,
            ],
            'published_at' => [
                'type' => self::FIELD_TYPE_DATE,
            ],
            'user_name' => [
                'virtual' => true,
            ],
        ];
    }

    public static function timestamps(): bool
    {
        return true;
    }

    public function getStatus(): string
    {
        return $this->get('status');
    }

    public function setOwnerId($ownerId): void
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
        return $this->get(Entity::CREATED_AT_FIELD);
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->get(Entity::UPDATED_AT_FIELD);
    }

    public function getPublishedAt(): ?\DateTimeImmutable
    {
        return $this->get('published_at');
    }

    public function setPublishedAt(?\DateTime $publishedAt): void
    {
        $this->set('published_at', $publishedAt);
    }
}
