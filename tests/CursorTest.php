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

use Access\Query\Cursor\CurrentIdsCursor;
use Access\Query\Cursor\PageCursor;
use Access\Query\Select;
use Tests\AbstractBaseTestCase;
use Tests\Fixtures\Entity\Project;

class CursorTest extends AbstractBaseTestCase
{
    public function testPageCursor(): void
    {
        $query = new Select(Project::class);
        $query->orderBy('id ASC');

        $cursor = new PageCursor();
        $query->applyCursor($cursor);

        $this->assertEquals(PageCursor::DEFAULT_PAGE_SIZE, $cursor->getPageSize());

        $this->assertEquals(
            'SELECT `projects`.* FROM `projects` ORDER BY id ASC LIMIT 50 OFFSET 0',
            $query->getSql(),
        );

        $this->assertEquals([], $query->getValues());

        $pageSize = 20;

        $cursor->setPage(3);
        $cursor->setPageSize($pageSize);
        $query->applyCursor($cursor);

        $this->assertEquals($pageSize, $cursor->getPageSize());

        $this->assertEquals(
            'SELECT `projects`.* FROM `projects` ORDER BY id ASC LIMIT 20 OFFSET 40',
            $query->getSql(),
        );
    }

    public function testCurrentIdsCursor(): void
    {
        $query = new Select(Project::class);
        $query->orderBy('RAND()');

        $cursor = new CurrentIdsCursor();
        $query->applyCursor($cursor);

        $this->assertEquals(
            'SELECT `projects`.* FROM `projects` ORDER BY RAND() LIMIT 50',
            $query->getSql(),
        );

        $this->assertEquals([], $query->getValues());

        $cursor->addCurrentIds([1, 2]);

        $query->applyCursor($cursor);

        $this->assertEquals(
            'SELECT `projects`.* FROM `projects` WHERE (projects.id NOT IN (:w0, :w1)) ORDER BY RAND() LIMIT 50',
            $query->getSql(),
        );

        $this->assertEquals(['w0' => 1, 'w1' => 2], $query->getValues());
    }
}
