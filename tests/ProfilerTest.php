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

namespace Tests;

use Tests\AbstractBaseTestCase;

class ProfilerTest extends AbstractBaseTestCase
{
    /**
     * @depends testInsert
     */
    public function testProfiler()
    {
        $profiler = self::$db->getProfiler();
        $export = $profiler->export();

        $this->assertIsFloat($export['duration']);
        $this->assertIsFloat($profiler->getTotalDuration());

        // create table and inserts
        $this->assertEquals(7, count($export['queries']));
    }
}
