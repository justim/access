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

use Access\Entity;
use Access\Entity\CreatableTrait;

/**
 * SAFETY Return types are not known, they are stored in an array config
 * @psalm-suppress MixedReturnStatement
 * @psalm-suppress MixedInferredReturnType
 */
class LogMessage extends Entity
{
    use CreatableTrait;

    public static function tableName(): string
    {
        return 'log_messages';
    }

    public static function fields(): array
    {
        return [
            'message' => [],
        ];
    }

    public function setMessage(string $message): void
    {
        $this->set('message', $message);
    }

    public function getMessage(): string
    {
        return $this->get('message');
    }
}
