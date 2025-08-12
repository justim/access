<?php

declare(strict_types=1);

namespace Tests\Unit\Query;

use Access\Exception\NotSupportedException;
use Access\Query\Select;
use Access\Query\Union;
use PHPUnit\Framework\TestCase;

use Tests\Fixtures\Entity\Project;

class UnionTest extends TestCase
{
    public function testSimpleUnionQuery(): void
    {
        $q1 = new Select(Project::class, 'p1');
        $q1->where('p1.status = ?', 'IN_PROGRESS');

        $q2 = new Select(Project::class, 'p2');
        $q2->where('p2.status = ?', 'NOT_STARTED');

        $query = new Union($q1);
        $query->addQuery($q2);

        $this->assertEquals(
            'SELECT `p1`.* FROM `projects` AS `p1` WHERE (p1.status = :u0w0) ' .
                'UNION SELECT `p2`.* FROM `projects` AS `p2` WHERE (p2.status = :u1w0)',
            $query->getSql(),
        );
        $this->assertEquals(
            ['u0w0' => 'IN_PROGRESS', 'u1w0' => 'NOT_STARTED'],
            $query->getValues(),
        );
    }

    public function testUnionQueryWithLimit(): void
    {
        $q1 = new Select(Project::class, 'p1');
        $q1->where('p1.status = ?', 'IN_PROGRESS');

        $q2 = new Select(Project::class, 'p2');
        $q2->where('p2.status = ?', 'NOT_STARTED');

        $query = new Union($q1);
        $query->addQuery($q2);
        $query->limit(1);

        $this->assertEquals(
            '(SELECT `p1`.* FROM `projects` AS `p1` WHERE (p1.status = :u0w0) ' .
                'UNION SELECT `p2`.* FROM `projects` AS `p2` WHERE (p2.status = :u1w0)) LIMIT 1',
            $query->getSql(),
        );
        $this->assertEquals(
            ['u0w0' => 'IN_PROGRESS', 'u1w0' => 'NOT_STARTED'],
            $query->getValues(),
        );
    }

    public function testUnionQueryWithSelect(): void
    {
        $query = new Union(new Select(Project::class, 'p1'));

        $this->expectException(NotSupportedException::class);
        $query->select('');
    }

    public function testUnionQueryWithVirtualFields(): void
    {
        $query = new Union(new Select(Project::class, 'p1'));

        $this->expectException(NotSupportedException::class);
        $query->addVirtualField('', '');
    }
}
