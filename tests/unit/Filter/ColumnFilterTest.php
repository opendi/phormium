<?php
namespace Phormium\Tests\Unit\Filter;

use Phormium\Exception\InvalidQueryException;
use Phormium\Filter\ColumnFilter;
use Phormium\Filter\Filter;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group filter
 */
class ColumnFilterTest extends TestCase {
    public function testFactory() {
        $filter = Filter::col('test', '=', 1);

        $this->assertInstanceOf(ColumnFilter::class, $filter);
        $this->assertSame('=', $filter->operation());
        $this->assertSame('test', $filter->column());
        $this->assertSame(1, $filter->value());
    }

    public function testFilterFromArray() {
        $actual = ColumnFilter::fromArray(['id', '=', 123]);

        $this->assertInstanceOf(ColumnFilter::class, $actual);
        $this->assertSame('id', $actual->column());
        $this->assertSame('=', $actual->operation());
        $this->assertSame(123, $actual->value());

        $actual = ColumnFilter::fromArray(['email', 'is null']);

        $this->assertInstanceOf(ColumnFilter::class, $actual);
        $this->assertSame('email', $actual->column());
        $this->assertSame('IS NULL', $actual->operation());
        $this->assertNull($actual->value());
    }

    public function testFilterFromArrayExceptionTooMany() {
        $this->expectExceptionMessage("Invalid filter sepecification");
        $this->expectException(InvalidQueryException::class);
        ColumnFilter::fromArray([1, 2, 3, 4, 5]);
    }

    public function testFilterFromArrayExceptionTooFew() {
        $this->expectExceptionMessage("Invalid filter sepecification");
        $this->expectException(InvalidQueryException::class);
        ColumnFilter::fromArray([1]);
    }

    public function testInvalidColumn() {
        $this->expectExceptionMessage("Argument \$column must be a string, integer given.");
        $this->expectException(InvalidQueryException::class);
        new ColumnFilter(1, "=", 1);
    }

    public function testInvalidOperation() {
        $this->expectExceptionMessage("Argument \$operation must be a string, integer given.");
        $this->expectException(InvalidQueryException::class);
        new ColumnFilter("foo", 1, 1);
    }

    public function testEqWrongParam() {
        $this->expectExceptionMessage("Filter = requires a scalar value, array given.");
        $this->expectException(InvalidQueryException::class);
        new ColumnFilter('test', '=', []);
    }

    public function testGt() {
        $filter = new ColumnFilter('test', '>', 123);

        $this->assertSame('test', $filter->column());
        $this->assertSame('>', $filter->operation());
        $this->assertSame(123, $filter->value());
    }

    public function testGtWrongParam() {
        $this->expectExceptionMessage("Filter > requires a scalar value, array given.");
        $this->expectException(InvalidQueryException::class);
        new ColumnFilter('test', '>', []);
    }

    public function testNeqWrongParam() {
        $this->expectExceptionMessage("Filter != requires a scalar value, array given.");
        $this->expectException(InvalidQueryException::class);
        new ColumnFilter('test', '!=', []);
    }

    public function testIn() {
        $filter = new ColumnFilter('test', 'in', [1, 2, 3]);

        $this->assertSame('test', $filter->column());
        $this->assertSame('IN', $filter->operation());
        $this->assertSame([1, 2, 3], $filter->value());

    }

    public function testInWrongParam1() {
        $this->expectExceptionMessage("Filter IN requires an array, integer given.");
        $this->expectException(InvalidQueryException::class);
        new ColumnFilter('test', 'in', 1);
    }

    public function testInWrongParam2() {
        $this->expectExceptionMessage("Filter IN requires an array, string given.");
        $this->expectException(InvalidQueryException::class);
        new ColumnFilter('test', 'in', "1");
    }

    public function testInWrongParam3() {
        $this->expectExceptionMessage("Filter IN requires an array, NULL given.");
        $this->expectException(InvalidQueryException::class);
        new ColumnFilter('test', 'in', null);
    }

    public function testInWrongParam4() {
        $this->expectExceptionMessage("Filter IN requires a non-empty array, empty array given.");
        $this->expectException(InvalidQueryException::class);
        new ColumnFilter('test', 'in', []);
    }

    public function testNotInWrongParam1() {
        $this->expectExceptionMessage("Filter NOT IN requires an array, integer given.");
        $this->expectException(InvalidQueryException::class);
        new ColumnFilter('test', 'not in', 1);
    }

    public function testNotInWrongParam2() {
        $this->expectExceptionMessage("Filter NOT IN requires an array, string given.");
        $this->expectException(InvalidQueryException::class);
        new ColumnFilter('test', 'not in', "1");
    }

    public function testNotInWrongParam3() {
        $this->expectExceptionMessage("Filter NOT IN requires an array, NULL given.");
        $this->expectException(InvalidQueryException::class);
        new ColumnFilter('test', 'not in', null);
    }

    public function testNotInWrongParam4() {
        $this->expectExceptionMessage("Filter NOT IN requires a non-empty array, empty array given.");
        $this->expectException(InvalidQueryException::class);
        new ColumnFilter('test', 'not in', []);
    }

    public function testBetween() {
        $filter = new ColumnFilter('test', 'between', ['x', 'y']);

        $this->assertSame('test', $filter->column());
        $this->assertSame('BETWEEN', $filter->operation());
        $this->assertSame(['x', 'y'], $filter->value());
    }

    public function testBetweenWrongParam1() {
        $this->expectExceptionMessage("Filter BETWEEN requires an array, string given.");
        $this->expectException(InvalidQueryException::class);
        new ColumnFilter('test', 'between', 'xxx');
    }

    public function testBetweenWrongParam2() {
        $this->expectExceptionMessage("Filter BETWEEN requires an array, NULL given.");
        $this->expectException(InvalidQueryException::class);
        new ColumnFilter('test', 'between', null);
    }

    public function testBetweenWrongParam3() {
        $this->expectExceptionMessage("Filter BETWEEN requires an array with 2 values, given array has 1 values.");
        $this->expectException(InvalidQueryException::class);
        new ColumnFilter('test', 'between', [1]);
    }

    public function testUnknownOp() {
        $this->expectExceptionMessage("Unknown filter operation [XXX]");
        $this->expectException(InvalidQueryException::class);
        new ColumnFilter('test', 'xxx');
    }
}
