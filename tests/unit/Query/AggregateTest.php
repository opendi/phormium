<?php

namespace Phormium\Tests\Unit\Query;

use Phormium\Exception\InvalidQueryException;
use Phormium\Query\Aggregate;
use PHPUnit\Framework\TestCase;

/**
 * @group query
 * @group unit
 */
class AggregateTest extends TestCase {
    public function testConstruct() {
        $agg = new Aggregate(Aggregate::AVERAGE, "foo");
        $this->assertSame("avg", $agg->type());
        $this->assertSame("foo", $agg->column());

        $agg = new Aggregate(Aggregate::COUNT);
        $this->assertSame("count", $agg->type());
        $this->assertSame("*", $agg->column());
    }

    public function testInvalidType() {
        $this->expectExceptionMessage("Invalid aggregate type [xxx].");
        $this->expectException(InvalidQueryException::class);
        $agg = new Aggregate('xxx', 'yyy');
    }

    public function testRequiresColumnError() {
        $this->expectExceptionMessage("Aggregate type [avg] requires a column to be given.");
        $this->expectException(InvalidQueryException::class);
        new Aggregate(Aggregate::AVERAGE);
    }
}
