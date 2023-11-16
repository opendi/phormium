<?php

namespace Phormium\Tests\Unit\Query;

use Phormium\Exception\OrmException;
use Phormium\Query\LimitOffset;
use PHPUnit\Framework\TestCase;

/**
 * @group query
 * @group unit
 */
class LimitOffsetTest extends TestCase {
    public function testConstruct() {
        $lo = new LimitOffset(10, 20);
        $this->assertSame(10, $lo->limit());
        $this->assertSame(20, $lo->offset());

        $lo = new LimitOffset(10);
        $this->assertSame(10, $lo->limit());
        $this->assertNull($lo->offset());
    }

    public function testInvalidLimit1() {
        $this->expectExceptionMessage("\$limit must be a positive integer or null.");
        $this->expectException(OrmException::class);
        new LimitOffset(-1);
    }

    public function testInvalidLimit2() {
        $this->expectExceptionMessage("\$limit must be a positive integer or null.");
        $this->expectException(OrmException::class);
        new LimitOffset('foo');
    }

    public function testInvalidOffset() {
        $this->expectExceptionMessage("\$offset must be a positive integer or null.");
        $this->expectException(OrmException::class);
        new LimitOffset(1, -1);
    }

    public function testOffsetWithoutLimit() {
        $this->expectExceptionMessage("\$offset cannot be given without a \$limit");
        $this->expectException(OrmException::class);
        new LimitOffset(null, 1);
    }
}
