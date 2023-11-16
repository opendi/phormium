<?php

namespace Phormium\Tests\Integration;

use Phormium\Orm;
use PHPUnit\Framework\TestCase;

abstract class DbTest extends TestCase {
    public static function setUpBeforeClass(): void {
        Orm::configure(PHORMIUM_CONFIG_FILE);
    }

    public static function tearDownAfterClass(): void {
        Orm::reset();
    }
}
