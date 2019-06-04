<?php

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
                'type' => 'int',
            ],
        ];
    }

    public static function timestamps(): bool
    {
        return true;
    }

    public function setOwnerId(int $email): void
    {
        $this->set('owner_id', $email);
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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->get('created_at');
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->get('updated_at');
    }
}
