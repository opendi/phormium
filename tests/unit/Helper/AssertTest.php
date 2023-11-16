<?php

namespace Phormium\Tests\Unit\Helper;

use Phormium\Helper\Assert;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * @group unit
 * @group helper
 */
class AssertTest extends TestCase {
    public function testIsInteger() {
        $this->assertTrue(Assert::isInteger(10));
        $this->assertTrue(Assert::isInteger(0));
        $this->assertTrue(Assert::isInteger(-10));
        $this->assertTrue(Assert::isInteger("10"));
        $this->assertTrue(Assert::isInteger("0"));
        $this->assertTrue(Assert::isInteger("-10"));

        $this->assertFalse(Assert::isInteger(10.6));
        $this->assertFalse(Assert::isInteger("10.6"));
        $this->assertFalse(Assert::isInteger("heavy metal"));
        $this->assertFalse(Assert::isInteger([]));
        $this->assertFalse(Assert::isInteger(new stdClass()));
        $this->assertFalse(Assert::isInteger(""));
        $this->assertFalse(Assert::isInteger("-"));
    }

    public function testIsPositiveInteger() {
        $this->assertTrue(Assert::isPositiveInteger(10));
        $this->assertTrue(Assert::isPositiveInteger(0));
        $this->assertTrue(Assert::isPositiveInteger("10"));
        $this->assertTrue(Assert::isPositiveInteger("0"));

        $this->assertFalse(Assert::isPositiveInteger(10.6));
        $this->assertFalse(Assert::isPositiveInteger("10.6"));
        $this->assertFalse(Assert::isPositiveInteger("heavy metal"));
        $this->assertFalse(Assert::isPositiveInteger([]));
        $this->assertFalse(Assert::isPositiveInteger(new stdClass()));
        $this->assertFalse(Assert::isPositiveInteger(""));
        $this->assertFalse(Assert::isPositiveInteger("-"));
        $this->assertFalse(Assert::isPositiveInteger(-10));
        $this->assertFalse(Assert::isPositiveInteger("-10"));
    }
}
