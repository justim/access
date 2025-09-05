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
use Tests\Fixtures\Repository\UserRepository;

use Access\Entity;
use Access\Entity\SoftDeletableTrait;
use Access\Entity\TimestampableTrait;
use Access\Schema\Field;
use Access\Schema\Table;
use Access\Schema\Type;
use Tests\Fixtures\UserStatus;

/**
 * SAFETY Return types are not known, they are stored in an array config
 * @psalm-suppress MixedReturnStatement
 * @psalm-suppress MixedInferredReturnType
 */
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

    public static function relations(): array
    {
        return [
            'owner_of' => [
                'target' => Project::class,
                'field' => 'owner_id',
                'cascade' => Cascade::deleteSame(),
            ],
        ];
    }

    public static function fields(): array
    {
        return [
            'role' => [
                'default' => 'USER',
            ],
            'profile_image_id' => [
                'type' => self::FIELD_TYPE_INT,
                'target' => ProfileImage::class,
                'cascade' => Cascade::deleteSame(),
            ],
            'status' => [
                'type' => self::FIELD_TYPE_ENUM,
                'enumName' => UserStatus::class,
                'default' => UserStatus::ACTIVE,
            ],
            'email' => [],
            'name' => [],
            'total_projects' => [
                'type' => self::FIELD_TYPE_INT,
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
        $table = new Table('users', hasCreatedAt: true, hasUpdatedAt: true, hasDeletedAt: true);

        $table->field('role', default: 'USER');

        $table->field(
            'profile_image_id',
            new Type\Reference(ProfileImage::class, Cascade::deleteSame()),
        );

        $table->field('status', new Type\Enum(UserStatus::class), UserStatus::ACTIVE);

        $table->field('email');

        $table->field('name');

        $totalProjects = new Field('total_projects', new Type\Integer());
        $totalProjects->markAsVirtual();
        $table->addField($totalProjects);

        return $table;
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

    public function setStatus(UserStatus $status): void
    {
        $this->set('status', $status);
    }

    public function getStatus(): UserStatus
    {
        return $this->get('status');
    }

    public function setProfileImageId(?int $profileImageId): void
    {
        $this->set('profile_image_id', $profileImageId);
    }
}
