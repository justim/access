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

namespace Tests\Base;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\Entity\LogMessage;
use Tests\Fixtures\Entity\User;
use Tests\Fixtures\MockClock;

abstract class BaseClockTest extends TestCase implements DatabaseBuilderInterface
{
    public function testCreatable(): void
    {
        $now = new DateTimeImmutable('2023-05-23 00:00:00');
        $clock = new MockClock($now);
        $db = static::createDatabaseWithMockClock($clock);

        $logMessage = new LogMessage();
        $logMessage->setMessage('Something happened!');
        $db->save($logMessage);

        // the mock datetime is set as the created at time
        $this->assertEquals($now, $logMessage->getCreatedAt());
    }

    public function testClockRoundtrip(): void
    {
        $now = new DateTimeImmutable('2023-05-23 00:00:00');
        $clock = new MockClock($now);
        $db = static::createDatabaseWithMockClock($clock);

        $user = new User();
        $user->setName('Dave');
        $db->save($user);

        // the mock datetime is set as the created at time
        $this->assertEquals($now, $user->getCreatedAt());
    }

    public function testClockUpdate(): void
    {
        $now = new DateTimeImmutable('2023-05-23 00:00:00');
        $clock = new MockClock($now);
        $db = static::createDatabaseWithMockClock($clock);

        $user = new User();
        $user->setName('Dave');
        $db->save($user);

        // the mock datetime is set as the created at time
        $this->assertEquals($now, $user->getCreatedAt());
        $this->assertEquals($now, $user->getUpdatedAt());

        $nextWeek = $now->modify('next week');
        $clock->set($nextWeek);

        $user->setName('Dave 2');
        $db->save($user);

        // the mock datetime is set as the created at time
        $this->assertEquals($now, $user->getCreatedAt());
        $this->assertEquals($nextWeek, $user->getUpdatedAt());
    }

    public function testClockSoftDelete(): void
    {
        $now = new DateTimeImmutable('2023-05-23 00:00:00');
        $clock = new MockClock($now);
        $db = static::createDatabaseWithMockClock($clock);

        $user = new User();
        $user->setName('Dave');
        $db->save($user);

        $db->softDelete($user);

        // the mock datetime is set as the deleted at time
        $this->assertEquals($now, $user->getDeletedAt());
    }
}
