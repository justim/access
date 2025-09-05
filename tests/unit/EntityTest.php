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

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tests\Fixtures\Entity\LogMessage;
use Tests\Fixtures\Entity\ProfileImage;
use Tests\Fixtures\Entity\Project;
use Tests\Fixtures\Entity\User;

class EntityTest extends TestCase
{
    public function testUserTableSchema(): void
    {
        // hand made
        $a = User::getTableSchema();

        // based on `fields`
        $b = User::getParentTableSchema();

        $this->assertEquals($a, $b);
    }

    public function testProjectTableSchema(): void
    {
        // hand made
        $a = Project::getTableSchema();

        // based on `fields`
        $b = Project::getParentTableSchema();

        $this->assertEquals($a, $b);
    }

    public function testProfileImageTableSchema(): void
    {
        // hand made
        $a = ProfileImage::getTableSchema();

        // based on `fields`
        $b = ProfileImage::getParentTableSchema();

        $this->assertEquals($a, $b);
    }

    public function testLogMessageTableSchema(): void
    {
        // hand made
        $a = LogMessage::getTableSchema();

        // based on `fields`
        $b = LogMessage::getParentTableSchema();

        $this->assertEquals($a, $b);
    }
}
