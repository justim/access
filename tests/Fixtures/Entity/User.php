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

use Tests\Fixtures\Repository\UserRepository;

use Access\Entity;
use Access\Entity\SoftDeletableTrait;
use Access\Entity\TimestampableTrait;

class User extends Entity
{
    use TimestampableTrait;
    use SoftDeletableTrait;

    public static function getRepository(): string
    {
        return UserRepository::class;
    }

    public static function tableName(): string
    {
        return 'users';
    }

    public static function fields(): array
    {
        return [
            'role' => [
                'default' => 'USER',
            ],
            'email' => [],
            'name' => [],
            'total_projects' => [
                'type' => self::FIELD_TYPE_INT,
                'virtual' => true,
            ],
        ];
    }

    public function setEmail(string $email): void
    {
        $this->set('email', $email);
    }

    public function getEmail(): string
    {
        return $this->get('email');
    }

    public function setName(string $name): void
    {
        $this->set('name', $name);
    }

    public function getName(): string
    {
        return $this->get('name');
    }

    public function getTotalProjects(): int
    {
        return $this->get('total_projects');
    }

    public function getUsername(): ?string
    {
        return $this->get('username');
    }

    public function overrideId(int $id): void
    {
        $this->set('id', $id);
    }
}
