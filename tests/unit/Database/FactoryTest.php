<?php

namespace Phormium\Tests\Unit\Database;

use Evenement\EventEmitter;
use Exception;
use Mockery as m;
use PDO;
use Phormium\Database\Factory;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group database
 */
class FactoryTest extends TestCase {
    private $config = [
        "db1" => [
            "dsn" => "sqlite:tmp/db1.db",
            "driver" => "sqlite",
            "username" => null,
            "password" => null,
            "attributes" => []
        ],
        "db2" => [
            "dsn" => "sqlite:tmp/db2.db",
            "driver" => "sqlite",
            "username" => null,
            "password" => null,
            "attributes" => []
        ]
    ];

    public function tearDown(): void {
        m::close();
    }

    public function testAttributes1() {
        $emitter = $this->getMockEmitter();

        $config = $this->config;
        $config['db1']['attributes'] = [
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ];

        $factory = new Factory($config, $emitter);
        $conn = $factory->newConnection('db1');
        $pdo = $conn->getPDO();

        $expected = PDO::FETCH_ASSOC;
        $actual = $pdo->getAttribute(PDO::ATTR_DEFAULT_FETCH_MODE);
        $this->assertSame($expected, $actual);
    }

    protected function getMockEmitter() {
        return m::mock(EventEmitter::class);
    }

    public function testAttributes2() {
        $emitter = $this->getMockEmitter();

        $config = $this->config;
        $config['db1']['attributes'] = [
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_BOTH
        ];

        $factory = new Factory($config, $emitter);
        $conn = $factory->newConnection('db1');
        $pdo = $conn->getPDO();

        $expected = PDO::FETCH_BOTH;
        $actual = $pdo->getAttribute(PDO::ATTR_DEFAULT_FETCH_MODE);
        $this->assertSame($expected, $actual);
    }

    public function testAttributesCannotChange() {
        $emitter = $this->getMockEmitter();

        $config = $this->config;
        $config['db1']['attributes'] = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT
        ];

        $factory = new Factory($config, $emitter);

        // Suppress the warning which breaks the test
        $conn = @$factory->newConnection("db1");
        $pdo = $conn->getPDO();

        // Error mode should be exception, even though it is set to a different
        // value in the settings
        $expected = PDO::ERRMODE_EXCEPTION;
        $actual = $pdo->getAttribute(PDO::ATTR_ERRMODE);
        $this->assertSame($expected, $actual);
    }

    public function testInvalidAttribute() {
        $this->expectExceptionMessage("Failed setting PDO attribute \"foo\" to \"bar\" on database \"db1\".");
        $this->expectException(Exception::class);
        $emitter = $this->getMockEmitter();

        $config = $this->config;
        $config['db1']['attributes'] = ["foo" => "bar"];

        $factory = new Factory($config, $emitter);
        @$factory->newConnection("db1");
    }

    public function testNotConfiguredException() {
        $this->expectExceptionMessage("Database \"db3\" is not configured.");
        $this->expectException(Exception::class);
        $emitter = $this->getMockEmitter();
        $config = $this->config;

        $factory = new Factory($config, $emitter);
        $factory->newConnection("db3");
    }
}
