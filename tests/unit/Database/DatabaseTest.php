<?php

namespace Phormium\Tests\Unit\Database;

use Evenement\EventEmitter;
use Exception;
use Mockery as m;
use Phormium\Database\Connection;
use Phormium\Database\Database;
use Phormium\Database\Factory;
use Phormium\Event;
use Phormium\Exception\DatabaseException;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group database
 */
class DatabaseTest extends TestCase {
    public function tearDown(): void {
        m::close();
    }

    public function testSetConnection() {
        $conn = m::mock(Connection::class);

        $database = $this->newDatabase();
        $database->setConnection('foo', $conn);

        $actual = $database->getConnection('foo');
        $this->assertSame($actual, $conn);
    }

    protected function newDatabase(Factory $factory = null, EventEmitter $emitter = null) {
        if (!isset($factory)) {
            $factory = m::mock(Factory::class);
        }

        if (!isset($emitter)) {
            $emitter = $this->getMockEmitter();
        }

        return new Database($factory, $emitter);
    }

    protected function getMockEmitter() {
        $emitter = m::mock(EventEmitter::class);

        $emitter->shouldReceive('on')->once()
            ->with(Event::QUERY_STARTED, m::type('callable'));

        $emitter->shouldReceive('emit');

        return $emitter;
    }

    public function testSetConnectionError() {
        $this->expectExceptionMessage("Connection \"foo\" is already connected.");
        $this->expectException(DatabaseException::class);
        $conn = m::mock(Connection::class);

        $database = $this->newDatabase();
        $database->setConnection('foo', $conn);
        $database->setConnection('foo', $conn);
    }

    public function testDisconnect() {
        $this->setDependencies(['testSetConnectionError']);
        $conn = m::mock(Connection::class);

        $database = $this->newDatabase();
        $database->setConnection('foo', $conn);

        $this->assertTrue($database->isConnected('foo'));

        $conn->shouldReceive('inTransaction')->once()->andReturn(false);

        $database->disconnect('foo');
        $this->assertFalse($database->isConnected('foo'));

        $database->disconnect('foo');
        $this->assertFalse($database->isConnected('foo'));

        // Check rollback
        $conn->shouldReceive('inTransaction')->once()->andReturn(true);
        $conn->shouldReceive('rollback')->once();
        $database->setConnection('foo', $conn);
        $database->disconnect('foo');
    }

    public function testDisconnectAll() {
        $conn1 = m::mock(Connection::class);
        $conn2 = m::mock(Connection::class);

        $database = $this->newDatabase();
        $database->setConnection('db1', $conn1);
        $database->setConnection('db2', $conn2);
        $database->begin();

        // In transaction is checked twice, once by disconnectAll(), once by disconnect()
        $conn1->shouldReceive('inTransaction')->once()->andReturn(false);
        $conn1->shouldReceive('inTransaction')->once()->andReturn(false);
        $conn2->shouldReceive('inTransaction')->once()->andReturn(true);
        $conn2->shouldReceive('inTransaction')->once()->andReturn(false);
        $conn2->shouldReceive('rollback')->once();

        $database->disconnectAll();

        $this->assertFalse($database->isConnected('db1'));
        $this->assertFalse($database->isConnected('db2'));
    }

    public function testBeginTwice() {
        $this->expectExceptionMessage("Already in transaction.");
        $this->expectException(DatabaseException::class);
        $database = $this->newDatabase();
        $database->begin();
        $database->begin();
    }

    public function testTransactionCommit() {
        $conn1 = m::mock(Connection::class);
        $conn2 = m::mock(Connection::class);

        $database = $this->newDatabase();
        $database->setConnection('db1', $conn1);
        $database->setConnection('db2', $conn2);

        $this->assertFalse($database->beginTriggered());
        $database->begin();
        $this->assertTrue($database->beginTriggered());

        $conn1->shouldReceive('inTransaction')->once()->andReturn(false);
        $conn2->shouldReceive('inTransaction')->once()->andReturn(true);
        $conn2->shouldReceive('commit')->once();

        $this->assertTrue($database->beginTriggered());
        $database->commit();
        $this->assertFalse($database->beginTriggered());
    }

    public function testCommitOutsideOfTransaction() {
        $this->expectExceptionMessage("Cannot commit. Not in transaction.");
        $this->expectException(DatabaseException::class);
        $database = $this->newDatabase();
        $database->commit();
    }

    public function testTransactionRollback() {
        $conn1 = m::mock(Connection::class);
        $conn2 = m::mock(Connection::class);

        $database = $this->newDatabase();
        $database->setConnection('db1', $conn1);
        $database->setConnection('db2', $conn2);

        $this->assertFalse($database->beginTriggered());
        $database->begin();
        $this->assertTrue($database->beginTriggered());

        $conn1->shouldReceive('inTransaction')->once()->andReturn(false);
        $conn2->shouldReceive('inTransaction')->once()->andReturn(true);
        $conn2->shouldReceive('rollback')->once();

        $this->assertTrue($database->beginTriggered());
        $database->rollback();
        $this->assertFalse($database->beginTriggered());
    }

    public function testRollbackOutsideOfTransaction() {
        $this->expectExceptionMessage("Cannot roll back. Not in transaction.");
        $this->expectException(DatabaseException::class);
        $database = $this->newDatabase();
        $database->rollback();
    }

    public function testTransactionCallback() {
        $conn = m::mock(Connection::class);

        $database = $this->newDatabase();
        $database->setConnection('db', $conn);

        $conn->shouldReceive('inTransaction')->once()->andReturn(true);
        $conn->shouldReceive('commit')->once();

        $this->assertFalse($database->beginTriggered());

        $database->transaction(function () use ($database) {
            $this->assertTrue($database->beginTriggered());
        });

        $this->assertFalse($database->beginTriggered());
    }

    public function testTransactionCallbackRollback() {
        $this->expectExceptionMessage("Transaction failed. Rolled back.");
        $this->expectException(DatabaseException::class);
        $conn = m::mock(Connection::class);

        $database = $this->newDatabase();
        $database->setConnection('db', $conn);

        $conn->shouldReceive('inTransaction')->once()->andReturn(true);
        $conn->shouldReceive('rollback')->once();

        $this->assertFalse($database->beginTriggered());

        $database->transaction(function () use ($database) {
            $this->assertTrue($database->beginTriggered());
            throw new Exception("#fail");
        });
    }

    public function testGetConnection() {
        $conn = m::mock(Connection::class);

        $factory = m::mock(Factory::class);
        $factory->shouldReceive('newConnection')
            ->once()
            ->with('db1')
            ->andReturn($conn);

        $database = $this->newDatabase($factory);

        $conn1 = $database->getConnection("db1");
        $this->assertSame($conn, $conn1);

        $conn2 = $database->getConnection("db1");
        $this->assertSame($conn, $conn2);
    }
}
