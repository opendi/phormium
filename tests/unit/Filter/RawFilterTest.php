<?php

namespace Phormium\Tests\Unit\Filter;

use Phormium\Filter\Filter;
use Phormium\Filter\RawFilter;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group filter
 */
class RawFilterTest extends TestCase {
    function testConstruction() {
        $condition = "lower(name) = ?";
        $arguments = ['foo'];

        $filter = new RawFilter($condition, $arguments);

        $this->assertSame($condition, $filter->condition());
        $this->assertSame($arguments, $filter->arguments());
    }

    function testFactory() {
        $condition = "lower(name) = ?";
        $arguments = ['foo'];

        $filter = Filter::raw($condition, $arguments);

        $this->assertSame($condition, $filter->condition());
        $this->assertSame($arguments, $filter->arguments());
    }
}
