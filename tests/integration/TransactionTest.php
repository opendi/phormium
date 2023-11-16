<?php

namespace Phormium\Tests\Integration;

use Exception;
use Phormium\Orm;
use Phormium\Query\QuerySegment;
use Phormium\Tests\Models\Person;
use PHPUnit\Framework\TestCase;

/**
 * @group transaction
 */
class TransactionTest extends TestCase {
    public static function setUpBeforeClass(): void {
        Orm::configure(PHORMIUM_CONFIG_FILE);
    }

    public function testManualBeginCommit() {
        $person = new Person();
        $person->name = 'Bruce Dickinson';
        $person->income = 12345;
        $person->save();

        $id = $person->id;

        Orm::begin();

        $p = Person::get($id);
        $p->income = 54321;
        $p->save();

        Orm::commit();

        $this->assertEquals(54321, Person::get($id)->income);
    }

    public function testManualBeginRollback() {
        $person = new Person();
        $person->name = 'Steve Harris';
        $person->income = 12345;
        $person->save();

        $id = $person->id;

        Orm::begin();

        $p = Person::get($id);
        $p->income = 54321;
        $p->save();

        Orm::rollback();

        $this->assertEquals(12345, Person::get($id)->income);
    }

    public function testCallbackTransactionCommit() {
        $person = new Person();
        $person->name = 'Dave Murray';
        $person->income = 12345;
        $person->save();

        $id = $person->id;

        Orm::transaction(function () use ($id) {
            $p = Person::get($id);
            $p->income = 54321;
            $p->save();
        });

        $this->assertEquals(54321, Person::get($id)->income);
    }

    public function testCallbackTransactionRollback() {
        $person = new Person();
        $person->name = 'Adrian Smith';
        $person->income = 12345;
        $person->save();

        $id = $person->id;

        try {
            Orm::transaction(function () use ($id) {
                $p = Person::get($id);
                $p->income = 54321;
                $p->save();

                throw new Exception("Aborting");
            });

            self::fail("This code should not be reachable.");

        } catch (Exception $ex) {
            // Expected. Do nothing.
        }

        // Check changes have been rolled back
        $this->assertEquals(12345, Person::get($id)->income);
    }

    public function testDisconnectRollsBackTransaction() {
        $person = new Person();
        $person->name = 'Nicko McBrain';
        $person->income = 12345;
        $person->save();

        $id = $person->id;

        Orm::begin();

        $p = Person::get($id);
        $p->income = 54321;
        $p->save();

        // This should roll back changes
        Orm::database()->disconnect('testdb');

        // So they won't be commited here
        Orm::commit();

        $this->assertEquals(12345, Person::get($id)->income);
    }

    public function testDisconnectAllRollsBackTransaction() {
        $person = new Person();
        $person->name = 'Nicko McBrain';
        $person->income = 12345;
        $person->save();

        $id = $person->id;

        Orm::begin();

        $p = Person::get($id);
        $p->income = 54321;
        $p->save();

        Orm::database()->disconnectAll();

        $this->assertEquals(12345, Person::get($id)->income);
    }

    public function testExecuteTransaction() {
        $person = new Person();
        $person->name = 'Janick Gers';
        $person->income = 100;
        $person->insert();

        $id = $person->id;
        $conn = Orm::database()->getConnection('testdb');

        $query = new QuerySegment("UPDATE person SET income = income + 1");

        Orm::begin();
        $conn->execute($query);
        Orm::rollback();

        $this->assertEquals(100, Person::get($id)->income);

        Orm::begin();
        $conn->execute($query);
        Orm::commit();

        $this->assertEquals(101, Person::get($id)->income);
    }

    public function testPreparedExecuteTransaction() {
        $person = new Person();
        $person->name = 'Janick Gers';
        $person->income = 100;
        $person->insert();

        $id = $person->id;
        $conn = Orm::database()->getConnection('testdb');

        $segment = new QuerySegment("UPDATE person SET income = ?", [200]);

        Orm::begin();
        $conn->preparedExecute($segment);
        Orm::rollback();

        $this->assertEquals(100, Person::get($id)->income);

        Orm::begin();
        $conn->preparedExecute($segment);
        Orm::commit();

        $this->assertEquals(200, Person::get($id)->income);
    }

    public function testRollbackBeforeBegin() {
        $this->expectExceptionMessage("Cannot roll back. Not in transaction.");
        $this->expectException(Exception::class);
        Orm::rollback();
    }

    public function testCommitBeforeBegin() {
        $this->expectExceptionMessage("Cannot commit. Not in transaction.");
        $this->expectException(Exception::class);
        Orm::commit();
    }

    public function testDoubleBegin() {
        Orm::begin();

        try {
            Orm::begin();
            $this->fail('Expected an exception here.');
        } catch (Exception $e) {
            $this->assertStringContainsString("Already in transaction.", $e->getMessage());
        }

        Orm::rollback();
    }
}
