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
use Access\Clause\Condition\NotEquals;
use Access\Clause\Condition\GreaterThan;
use Access\Clause\Condition\LessThan;
use Access\Clause\Condition\In;
use Access\Clause\Condition\NotIn;
use Access\Clause\Condition\IsNull;
use Access\Clause\Condition\IsNotNull;
use Access\Clause\Condition\Raw;
use Access\Clause\Condition\Relation;
use Access\Clause\Field;
use Access\Clause\Filter\Unique;
use Access\Clause\Multiple;
use Access\Clause\MultipleOr;
use Access\Clause\OrderBy\Ascending;
use Access\Clause\OrderBy\Descending;
use Access\Clause\OrderBy\Random;
use Tests\AbstractBaseTestCase;

/**
 * @psalm-suppress InternalClass
 * @psalm-suppress InternalMethod
 */
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

    public function testClauseEquals(): void
    {
        $one = new Equals('field', 'value');

        $this->assertTrue(
            $one->equals($one),
            'Equals clauses with same field and value should be equal',
        );
    }

    public function testClauseEqualsMultiple(): void
    {
        $one = new Multiple(new Equals('field', 'value'), new Equals('field', 'value'));

        $this->assertTrue(
            $one->equals($one),
            'Equals clauses with same field and value should be equal',
        );
    }

    public function testConditionEqualsWithSameFieldAndValue(): void
    {
        $one = new Equals('field', 'value');
        $two = new Equals('field', 'value');

        $this->assertTrue(
            $one->equals($two),
            'Equals conditions with same field and value should be equal',
        );
    }

    public function testConditionEqualsWithDifferentValues(): void
    {
        $one = new Equals('field', 'value1');
        $two = new Equals('field', 'value2');

        $this->assertFalse(
            $one->equals($two),
            'Equals conditions with different values should not be equal',
        );
    }

    public function testConditionEqualsWithDifferentFields(): void
    {
        $one = new Equals('field1', 'value');
        $two = new Equals('field2', 'value');

        $this->assertFalse(
            $one->equals($two),
            'Equals conditions with different fields should not be equal',
        );
    }

    public function testConditionEqualsWithFieldObjects(): void
    {
        $one = new Equals(new Field('field'), 'value');
        $two = new Equals(new Field('field'), 'value');

        $this->assertTrue(
            $one->equals($two),
            'Equals conditions with Field objects should be equal when field names match',
        );
    }

    public function testDifferentConditionTypesNotEqual(): void
    {
        $equals = new Equals('field', 'value');
        $notEquals = new NotEquals('field', 'value');

        $this->assertFalse(
            $equals->equals($notEquals),
            'Different condition types should not be equal',
        );
    }

    public function testComparisonConditionsEquality(): void
    {
        $greaterThan1 = new GreaterThan('field', 10);
        $greaterThan2 = new GreaterThan('field', 10);
        $greaterThan3 = new GreaterThan('field', 20);

        $this->assertTrue(
            $greaterThan1->equals($greaterThan2),
            'GreaterThan conditions with same parameters should be equal',
        );
        $this->assertFalse(
            $greaterThan1->equals($greaterThan3),
            'GreaterThan conditions with different values should not be equal',
        );

        $lessThan = new LessThan('field', 10);
        $this->assertFalse(
            $greaterThan1->equals($lessThan),
            'GreaterThan and LessThan should not be equal',
        );
    }

    public function testInConditionsEquality(): void
    {
        $in1 = new In('field', [1, 2, 3]);
        $in2 = new In('field', [1, 2, 3]);
        $in3 = new In('field', [1, 2, 4]);

        $this->assertTrue($in1->equals($in2), 'In conditions with same arrays should be equal');
        $this->assertFalse(
            $in1->equals($in3),
            'In conditions with different arrays should not be equal',
        );

        $notIn1 = new NotIn('field', [1, 2, 3]);
        $this->assertFalse($in1->equals($notIn1), 'In and NotIn should not be equal');
    }

    public function testNullConditionsEquality(): void
    {
        $isNull1 = new IsNull('field');
        $isNull2 = new IsNull('field');
        $isNull3 = new IsNull('other_field');

        $this->assertTrue(
            $isNull1->equals($isNull2),
            'IsNull conditions with same field should be equal',
        );
        $this->assertFalse(
            $isNull1->equals($isNull3),
            'IsNull conditions with different fields should not be equal',
        );

        $isNotNull1 = new IsNotNull('field');
        $this->assertFalse(
            $isNull1->equals($isNotNull1),
            'IsNull and IsNotNull should not be equal',
        );
    }

    public function testRawConditionsEquality(): void
    {
        $raw1 = new Raw('custom_sql = ?', 'value');
        $raw2 = new Raw('custom_sql = ?', 'value');
        $raw3 = new Raw('other_sql = ?', 'value');

        $this->assertTrue(
            $raw1->equals($raw2),
            'Raw conditions with same SQL and value should be equal',
        );
        $this->assertFalse(
            $raw1->equals($raw3),
            'Raw conditions with different SQL should not be equal',
        );
    }

    public function testRelationConditionsEquality(): void
    {
        $rel1 = new Relation('field1', 'field2');
        $rel2 = new Relation('field1', 'field2');
        $rel3 = new Relation('field1', 'field3');

        $this->assertTrue(
            $rel1->equals($rel2),
            'Relation conditions with same fields should be equal',
        );
        $this->assertFalse(
            $rel1->equals($rel3),
            'Relation conditions with different fields should not be equal',
        );
    }

    public function testOrderByEquality(): void
    {
        $ascending1 = new Ascending('field');
        $ascending2 = new Ascending('field');
        $ascending3 = new Ascending('other_field');

        $this->assertTrue(
            $ascending1->equals($ascending2),
            'Ascending order by with same field should be equal',
        );
        $this->assertFalse(
            $ascending1->equals($ascending3),
            'Ascending order by with different fields should not be equal',
        );

        $desc1 = new Descending('field');
        $this->assertFalse(
            $ascending1->equals($desc1),
            'Ascending and Descending should not be equal',
        );
    }

    public function testRandomOrderByEquality(): void
    {
        $random1 = new Random();
        $random2 = new Random();

        $this->assertTrue($random1->equals($random2), 'Random order by clauses should be equal');
    }

    public function testFilterEquality(): void
    {
        $unique1 = new Unique('field');
        $unique2 = new Unique('field');
        $unique3 = new Unique('other_field');

        $this->assertTrue(
            $unique1->equals($unique2),
            'Unique filters with same field should be equal',
        );
        $this->assertFalse(
            $unique1->equals($unique3),
            'Unique filters with different fields should not be equal',
        );
    }

    public function testMultipleClauseEquality(): void
    {
        $multiple1 = new Multiple(new Equals('field1', 'value1'), new GreaterThan('field2', 10));
        $multiple2 = new Multiple(new Equals('field1', 'value1'), new GreaterThan('field2', 10));
        $multiple3 = new Multiple(new Equals('field1', 'value2'), new GreaterThan('field2', 10));

        $this->assertTrue(
            $multiple1->equals($multiple2),
            'Multiple clauses with same conditions should be equal',
        );
        $this->assertFalse(
            $multiple1->equals($multiple3),
            'Multiple clauses with different conditions should not be equal',
        );
    }

    public function testMultipleOrClauseEquality(): void
    {
        $multipleOr1 = new MultipleOr(
            new Equals('field1', 'value1'),
            new Equals('field2', 'value2'),
        );
        $multipleOr2 = new MultipleOr(
            new Equals('field1', 'value1'),
            new Equals('field2', 'value2'),
        );

        $this->assertTrue(
            $multipleOr1->equals($multipleOr2),
            'MultipleOr clauses with same conditions should be equal',
        );

        $multiple = new Multiple(new Equals('field1', 'value1'), new Equals('field2', 'value2'));

        $this->assertFalse(
            $multipleOr1->equals($multiple),
            'MultipleOr and Multiple should not be equal',
        );
    }

    public function testEmptyMultipleClauseEquality(): void
    {
        $empty1 = new Multiple();
        $empty2 = new Multiple();

        $this->assertTrue($empty1->equals($empty2), 'Empty Multiple clauses should be equal');

        $emptyOr1 = new MultipleOr();
        $emptyOr2 = new MultipleOr();

        $this->assertTrue($emptyOr1->equals($emptyOr2), 'Empty MultipleOr clauses should be equal');
        $this->assertFalse(
            $empty1->equals($emptyOr1),
            'Empty Multiple and MultipleOr should not be equal - they are different types',
        );
    }

    public function testCrossTypeClauseInequality(): void
    {
        $condition = new Equals('field', 'value');
        $orderBy = new Ascending('field');
        $filter = new Unique('field');

        $this->assertFalse(
            $condition->equals($orderBy),
            'Condition and OrderBy should not be equal',
        );
        $this->assertFalse($condition->equals($filter), 'Condition and Filter should not be equal');
        $this->assertFalse($orderBy->equals($filter), 'OrderBy and Filter should not be equal');
    }

    public function testConditionEqualityWithNullValues(): void
    {
        $null1 = new Equals('field', null);
        $null2 = new Equals('field', null);
        $notNull = new Equals('field', 'value');

        $this->assertTrue($null1->equals($null2), 'Conditions with null values should be equal');
        $this->assertFalse(
            $null1->equals($notNull),
            'Null and non-null conditions should not be equal',
        );
    }

    public function testConditionEqualityWithArrayValues(): void
    {
        $array1 = new In('field', []);
        $array2 = new In('field', []);
        $nonEmpty = new In('field', [1, 2, 3]);

        $this->assertTrue($array1->equals($array2), 'Conditions with empty arrays should be equal');
        $this->assertFalse(
            $array1->equals($nonEmpty),
            'Empty and non-empty array conditions should not be equal',
        );
    }

    public function testOrderByWithFieldObjects(): void
    {
        $ascending1 = new Ascending(new Field('field'));
        $ascending2 = new Ascending(new Field('field'));
        $ascending3 = new Ascending(new Field('other_field'));

        $this->assertTrue(
            $ascending1->equals($ascending2),
            'Ascending order by with Field objects should be equal when field names match',
        );
        $this->assertFalse(
            $ascending1->equals($ascending3),
            'Ascending order by with different Field objects should not be equal',
        );
    }

    public function testComplexMultipleClauseEquality(): void
    {
        $inner1 = new Multiple(new Equals('field1', 'value1'), new GreaterThan('field2', 10));
        $inner2 = new Multiple(new Equals('field1', 'value1'), new GreaterThan('field2', 10));
        $outer1 = new Multiple($inner1, new LessThan('field3', 20));
        $outer2 = new Multiple($inner2, new LessThan('field3', 20));

        $this->assertTrue(
            $outer1->equals($outer2),
            'Complex nested Multiple clauses should be equal when all conditions match',
        );
    }

    public function testMixedClauseTypesInMultiple(): void
    {
        $mixed1 = new Multiple(
            new Equals('field1', 'value1'),
            new Ascending('field2'),
            new Unique('field3'),
        );
        $mixed2 = new Multiple(
            new Equals('field1', 'value1'),
            new Ascending('field2'),
            new Unique('field3'),
        );

        $this->assertTrue(
            $mixed1->equals($mixed2),
            'Multiple clauses with mixed clause types should be equal when all match',
        );
    }

    public function testConditionWithFieldReference(): void
    {
        $field1 = new Field('field1');
        $field2 = new Field('field2');

        $relation1 = new Relation($field1, $field2);
        $relation2 = new Relation($field1, $field2);
        $relation3 = new Relation($field1, new Field('field3'));

        $this->assertTrue(
            $relation1->equals($relation2),
            'Relations with same Field objects should be equal',
        );
        $this->assertFalse(
            $relation1->equals($relation3),
            'Relations with different second Field objects should not be equal',
        );
    }

    public function testMultipleOrWithDifferentOrder(): void
    {
        $multipleOr1 = new MultipleOr(
            new Equals('field1', 'value1'),
            new Equals('field2', 'value2'),
        );
        $multipleOr2 = new MultipleOr(
            new Equals('field2', 'value2'),
            new Equals('field1', 'value1'),
        );

        // Order matters in Multiple clauses due to SQL generation
        $this->assertFalse(
            $multipleOr1->equals($multipleOr2),
            'MultipleOr clauses with different order should not be equal',
        );
    }

    public function testConditionEqualityWithIteratorValues(): void
    {
        $arrayIterator1 = new \ArrayIterator([1, 2, 3]);
        $arrayIterator2 = new \ArrayIterator([1, 2, 3]);

        $in1 = new In('field', $arrayIterator1);
        $in2 = new In('field', $arrayIterator2);

        $this->assertTrue(
            $in1->equals($in2),
            'In conditions with ArrayIterator values should be equal when contents match',
        );
    }

    public function testMultipleClauseWithSingleItem(): void
    {
        $single1 = new Multiple(new Equals('field', 'value'));
        $single2 = new Multiple(new Equals('field', 'value'));
        $direct = new Equals('field', 'value');

        $this->assertTrue(
            $single1->equals($single2),
            'Multiple clauses with single item should be equal',
        );
        $this->assertFalse(
            $single1->equals($direct),
            'Multiple clause with single item should not equal direct condition',
        );
    }

    public function testEqualityValues(): void
    {
        // Test 1: Different scalar values should not be equal
        $condition1 = new Equals('field', 'value1');
        $condition2 = new Equals('field', 'value2');
        $this->assertFalse(
            $condition1->equals($condition2),
            'Conditions with different scalar values should not be equal',
        );

        // Test 2: Different numeric values should not be equal
        $greaterThan1 = new GreaterThan('score', 10);
        $greaterThan2 = new GreaterThan('score', 20);
        $this->assertFalse(
            $greaterThan1->equals($greaterThan2),
            'GreaterThan conditions with different numeric values should not be equal',
        );

        // Test 3: Different array contents should not be equal
        $in1 = new In('tags', ['php', 'mysql']);
        $in2 = new In('tags', ['php', 'sqlite']);
        $this->assertFalse(
            $in1->equals($in2),
            'In conditions with different array contents should not be equal',
        );

        // Test 4: Complex multiple clauses with different values should not be equal
        $multiple1 = new Multiple(new Equals('status', 'active'), new GreaterThan('score', 100));
        $multiple2 = new Multiple(new Equals('status', 'active'), new GreaterThan('score', 200));
        $this->assertFalse(
            $multiple1->equals($multiple2),
            'Multiple clauses with different nested values should not be equal',
        );

        // Test 5: OrderBy with different fields should not be equal
        $ascending1 = new Ascending('created_at');
        $ascending2 = new Ascending('updated_at');
        $this->assertFalse(
            $ascending1->equals($ascending2),
            'OrderBy clauses with different fields should not be equal',
        );

        // Test 6: Verify that identical clauses still work correctly
        $mixed1 = new Multiple(
            new Equals('type', 'user'),
            new In('role', ['admin', 'editor']),
            new GreaterThan('last_login', '2023-01-01'),
        );
        $mixed2 = new Multiple(
            new Equals('type', 'user'),
            new In('role', ['admin', 'editor']),
            new GreaterThan('last_login', '2023-01-01'),
        );
        $this->assertTrue($mixed1->equals($mixed2), 'Identical complex clauses should be equal');
    }

    public function testStrictTypeCheckingInEquals(): void
    {
        // Test that different clause types are never equal, even if they might generate similar SQL

        // Test Multiple vs MultipleOr - they should never be equal
        $multiple = new Multiple(new Equals('field', 'value'));
        $multipleOr = new MultipleOr(new Equals('field', 'value'));
        $this->assertFalse(
            $multiple->equals($multipleOr),
            'Multiple and MultipleOr should never be equal - different types',
        );
        $this->assertFalse(
            $multipleOr->equals($multiple),
            'MultipleOr and Multiple should never be equal - different types',
        );

        // Test different condition types with same field and value
        $equals = new Equals('field', null);
        $isNull = new IsNull('field');
        $this->assertFalse(
            $equals->equals($isNull),
            'Equals(field, null) and IsNull(field) should not be equal - different types',
        );

        $notEquals = new NotEquals('field', null);
        $isNotNull = new IsNotNull('field');
        $this->assertFalse(
            $notEquals->equals($isNotNull),
            'NotEquals(field, null) and IsNotNull(field) should not be equal - different types',
        );

        // Test different OrderBy types
        $ascending = new Ascending('field');
        $descending = new Descending('field');
        $this->assertFalse(
            $ascending->equals($descending),
            'Ascending and Descending should never be equal - different types',
        );

        // Test condition inheritance hierarchy - ensure subclasses are distinct
        $greaterThan = new GreaterThan('field', 10);
        $lessThan = new LessThan('field', 10);
        $this->assertFalse(
            $greaterThan->equals($lessThan),
            'GreaterThan and LessThan should never be equal - different types',
        );

        // Test In vs NotIn
        $in = new In('field', [1, 2, 3]);
        $notIn = new NotIn('field', [1, 2, 3]);
        $this->assertFalse(
            $in->equals($notIn),
            'In and NotIn should never be equal - different types',
        );
    }

    public function testSameTypeEquality(): void
    {
        // Ensure that same types with same parameters are still equal after strict checking

        // Multiple with same conditions
        $multiple1 = new Multiple(new Equals('a', 1), new GreaterThan('b', 2));
        $multiple2 = new Multiple(new Equals('a', 1), new GreaterThan('b', 2));
        $this->assertTrue($multiple1->equals($multiple2), 'Same Multiple types should be equal');

        // MultipleOr with same conditions
        $multipleOr1 = new MultipleOr(new Equals('a', 1), new LessThan('b', 5));
        $multipleOr2 = new MultipleOr(new Equals('a', 1), new LessThan('b', 5));
        $this->assertTrue(
            $multipleOr1->equals($multipleOr2),
            'Same MultipleOr types should be equal',
        );

        // Same condition types
        $equals1 = new Equals('field', 'value');
        $equals2 = new Equals('field', 'value');
        $this->assertTrue($equals1->equals($equals2), 'Same Equals conditions should be equal');

        // Same OrderBy types
        $asc1 = new Ascending('field');
        $asc2 = new Ascending('field');
        $this->assertTrue($asc1->equals($asc2), 'Same Ascending orders should be equal');
    }

    public function testEmptyClauseTypeDistinction(): void
    {
        // Test that even empty clauses of different types are not equal
        $emptyMultiple = new Multiple();
        $emptyMultipleOr = new MultipleOr();

        $this->assertFalse(
            $emptyMultiple->equals($emptyMultipleOr),
            'Empty Multiple and empty MultipleOr should not be equal - different types',
        );
        $this->assertFalse(
            $emptyMultipleOr->equals($emptyMultiple),
            'Empty MultipleOr and empty Multiple should not be equal - different types',
        );

        // But same types should be equal
        $emptyMultiple2 = new Multiple();
        $emptyMultipleOr2 = new MultipleOr();

        $this->assertTrue(
            $emptyMultiple->equals($emptyMultiple2),
            'Empty Multiple clauses of same type should be equal',
        );
        $this->assertTrue(
            $emptyMultipleOr->equals($emptyMultipleOr2),
            'Empty MultipleOr clauses of same type should be equal',
        );
    }
}
