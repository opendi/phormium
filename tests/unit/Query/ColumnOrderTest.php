<?php

namespace Phormium\Tests\Unit\Query;

use Phormium\Exception\OrmException;
use Phormium\Query\ColumnOrder;
use PHPUnit\Framework\TestCase;

/**
 * @group query
 * @group unit
 */
class ColumnOrderTest extends TestCase {
    public function testConstruct() {
        $order = new ColumnOrder("foo", "asc");

        $this->assertSame("foo", $order->column());
        $this->assertSame("asc", $order->direction());

        $order = new ColumnOrder("bar", "desc");

        $this->assertSame("bar", $order->column());
        $this->assertSame("desc", $order->direction());
    }

    public function testFactories() {
        $order = ColumnOrder::asc("foo");

        $this->assertSame("foo", $order->column());
        $this->assertSame("asc", $order->direction());

        $order = ColumnOrder::desc("bar");

        $this->assertSame("bar", $order->column());
        $this->assertSame("desc", $order->direction());
    }

    public function testInvalidDirection() {
        $this->expectExceptionMessage("Invalid \$direction [bar]. Expected one of [asc, desc]");
        $this->expectException(OrmException::class);
        new ColumnOrder("foo", "bar");
    }

    public function testInvalidColumn() {
        $this->expectExceptionMessage("Invalid \$column type [array], expected string.");
        $this->expectException(OrmException::class);
        new ColumnOrder([], "asc");
    }
}
