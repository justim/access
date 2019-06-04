<?php

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
