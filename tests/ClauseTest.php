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

use Access\Clause\Condition\Equals;
use Access\Clause\Multiple;
use Access\Clause\MultipleOr;
use Tests\AbstractBaseTestCase;

class ClauseTest extends AbstractBaseTestCase
{
    public function testCountableMultiple(): void
    {
        $multiple = new Multiple();
        $this->assertEquals(0, count($multiple));

        $multiple->add(new Equals('field', 'value'));
        $this->assertEquals(1, count($multiple));

        $multiple->add(new Equals('field', 'value'));
        $this->assertEquals(2, count($multiple));
    }

    public function testCountableMultipleOr(): void
    {
        $multiple = new MultipleOr();
        $this->assertEquals(0, count($multiple));

        $multiple->add(new Equals('field', 'value'));
        $this->assertEquals(1, count($multiple));

        $multiple->add(new Equals('field', 'value'));
        $this->assertEquals(2, count($multiple));
    }
}
