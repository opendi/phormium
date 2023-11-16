<?php

namespace Phormium\Tests\Unit\Query;

use Phormium\Exception\OrmException;
use Phormium\Query\ColumnOrder;
use Phormium\Query\OrderBy;
use PHPUnit\Framework\TestCase;

/**
 * @group query
 * @group unit
 */
class OrderByTest extends TestCase {
    public function testConstruct() {
        $co1 = new ColumnOrder("foo", ColumnOrder::ASCENDING);
        $co2 = new ColumnOrder("foo", ColumnOrder::ASCENDING);
        $orderBy = new OrderBy([$co1, $co2]);

        $this->assertCount(2, $orderBy->orders());
        $this->assertSame($co1, $orderBy->orders()[0]);
        $this->assertSame($co2, $orderBy->orders()[1]);
    }

    public function testAdding() {
        $co1 = new ColumnOrder("foo", ColumnOrder::ASCENDING);
        $co2 = new ColumnOrder("foo", ColumnOrder::ASCENDING);

        $ob1 = new OrderBy([$co1]);
        $ob2 = $ob1->withAdded($co2);

        $this->assertNotSame($ob1, $ob2);

        $this->assertCount(1, $ob1->orders());
        $this->assertSame($co1, $ob1->orders()[0]);

        $this->assertCount(2, $ob2->orders());
        $this->assertSame($co1, $ob2->orders()[0]);
        $this->assertSame($co2, $ob2->orders()[1]);
    }

    public function testEmptyOrder() {
        $this->expectException(OrmException::class);
        $this->expectExceptionMessage("OrderBy needs at least one ColumnOrder element, empty array given.");
        new OrderBy([]);
    }

    public function testInvalidOrder() {
        $this->expectExceptionMessage("Expected \$orders to be instances of Phormium\Query\ColumnOrder. Given [string].");
        $this->expectException(OrmException::class);
        new OrderBy(["foo"]);
    }
}
