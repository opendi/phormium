<?php

namespace Phormium\Tests\Integration;

use Exception;
use Phormium\Database\Driver;
use Phormium\Exception\ModelNotFoundException;
use Phormium\Orm;
use Phormium\Tests\Models\Person;
use Phormium\Tests\Models\Asset;
use Phormium\Tests\Models\Contact;
use Phormium\Tests\Models\PkLess;
use Phormium\Tests\Models\Trade;
use PHPUnit\Framework\TestCase;

/**
 * @group model
 */
class ModelTest extends TestCase {
    public static function setUpBeforeClass(): void {
        Orm::configure(PHORMIUM_CONFIG_FILE);
    }

    public function testNewPerson() {
        $now = date('Y-m-d H:i:s');

        $p = new Person();
        $p->name = 'Test Person';
        $p->email = 'test.person@example.com';
        $p->birthday = '1970-01-01';
        $p->created = $now;
        $p->income = 50.37;

        $this->assertNull($p->id);
        $p->save();
        $this->assertNotNull($p->id);

        $id = $p->id;

        // Load it from the database
        $p2 = Person::get($id);
        $this->assertInstanceOf(Person::class, $p2);
        $this->assertEquals($p, $p2);

        // Alternative get
        $p3 = Person::get([$id]);
        $this->assertInstanceOf(Person::class, $p3);
        $this->assertEquals($p, $p3);
    }

    public function testBooleanFalse() {
        $p = new Person();
        $p->name = "Courtney Love";
        $p->is_cool = false;
        $p->save();

        $p2 = Person::get($p->id);

        // The postgres driver retrieves actual booleans, others return 1/0
        $driver = Orm::database()->getConnection('testdb')->getDriver();
        if ($driver == Driver::PGSQL) {
            $this->assertFalse($p2->is_cool);
        } else {
            $this->assertSame('0', $p2->is_cool);
        }
    }

    public function testBooleanTrue() {
        $p = new Person();
        $p->name = "Courtney Barnett";
        $p->is_cool = true;
        $p->save();

        $p2 = Person::get($p->id);

        // The postgres driver retrieves actual booleans, others return 1/0
        $driver = Orm::database()->getConnection('testdb')->getDriver();
        if ($driver == Driver::PGSQL) {
            $this->assertTrue($p2->is_cool);
        } else {
            $this->assertSame('1', $p2->is_cool);
        }
    }

    public function testNewTrade() {
        $date = '2013-07-17';
        $no = 12345;

        // Delete if it exists
        Trade::objects()
            ->filter('tradedate', '=', $date)
            ->filter('tradeno', '=', $no)
            ->delete();

        $t = new Trade();
        $t->tradedate = $date;
        $t->tradeno = $no;
        $t->price = 123.45;
        $t->quantity = 321;

        // Check insert does not change the object
        $t0 = clone $t;

        $t->insert();

        $this->assertEquals($t, $t0);

        // Load it from the database
        $t2 = Trade::get($date, $no);
        $this->assertInstanceOf(Trade::class, $t2);
        $this->assertEquals($t, $t2);

        // Alternative get
        $t3 = Trade::get([$date, $no]);
        $this->assertInstanceOf(Trade::class, $t3);
        $this->assertEquals($t, $t3);
    }

    public function testNewTradeHalfPkSet() {
        $this->expectExceptionMessage("Cannot insert. Primary key column(s) not set.");
        $this->expectException(Exception::class);
        $t = new Trade();
        $t->tradedate = date('Y-m-d');
        $t->insert();
    }

    public function testNewPersonAssignedPK() {
        $id = 100;

        // Delete person id 100 if it already exists
        Person::objects()->filter('id', '=', $id)->delete();

        $p = new Person();
        $p->id = $id;
        $p->name = 'Test Person';
        $p->email = 'test.person@example.com';
        $p->save();

        $this->assertEquals($id, $p->id);

        // Load it from the database
        $p2 = Person::get($id);
        $this->assertEquals($p, $p2);
    }

    public function testNewPersonFromArray() {
        $p = Person::fromArray(
            [
                'name' => 'Peter Peterson',
                'email' => 'peter@peterson.com'
            ]
        );

        // Perform INSERT
        $p->save();
        $this->assertNotNull($p->id);

        $id = $p->id;

        // Load it from the database
        $p2 = Person::get($id);
        $this->assertEquals($p, $p2);

        // Perform UPDATE
        $p2->email = 'peter2@peterson.com';
        $p2->save();

        // Load from database
        $p3 = Person::get($id);
        $this->assertEquals($p2, $p3);
        $this->assertEquals($id, $p3->id);
    }

    public function testFromJSON() {
        $actual = Person::fromJSON(
            '{"id":"101","name":"Jack Jackson","email":"jack@jackson.org","birthday":"1980-03-14",' .
            '"created":"2000-03-07 10:45:13","income":"12345.67"}'
        );

        $expected = new Person();
        $expected->id = 101;
        $expected->name = 'Jack Jackson';
        $expected->email = 'jack@jackson.org';
        $expected->birthday = '1980-03-14';
        $expected->created = '2000-03-07 10:45:13';
        $expected->income = 12345.67;

        $this->assertEquals($expected, $actual);
    }

    public function testFromYAML() {
        $yaml = implode("\n", [
            'id: 101',
            'name: "Jack Jackson"',
            'email: "jack@jackson.org"',
            'birthday: "1980-03-14"',
            'created: "2000-03-07 10:45:13"',
            'income: 12345.67',
        ]);

        $actual = Person::fromYAML($yaml);

        $expected = new Person();
        $expected->id = 101;
        $expected->name = 'Jack Jackson';
        $expected->email = 'jack@jackson.org';
        $expected->birthday = '1980-03-14';
        $expected->created = '2000-03-07 10:45:13';
        $expected->income = 12345.67;

        $this->assertEquals($expected, $actual);
    }

    public function testFromJSONError() {
        $this->expectExceptionMessage("Failed parsing JSON");
        $this->expectException(Exception::class);
        Person::fromJSON('[[[');
    }

    public function testInvalidData() {
        $this->expectExceptionMessage("Given argument is not an array");
        $this->expectException(Exception::class);
        $p = Person::fromArray('invalid data');
    }

    public function testInvalidProperty() {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Property [xxx] does not exist in class [Phormium\Tests\Models\Person]");
        $p = Person::fromArray([
            'name' => 'Peter Peterson',
            'xxx' => 'peter@peterson.com' // doesn't exist
        ], true);
    }

    public function testUpdate() {
        // Insert
        $person1 = new Person();
        $person1->name = 'foo';
        $person1->save();

        $id = $person1->id;

        // Load
        $person2 = Person::get($id);
        $this->assertSame('foo', $person2->name);

        // Modify + update
        $person2->name = 'bar';
        $person2->save();

        // Load
        $person3 = Person::get($id);
        $this->assertSame('bar', $person3->name);
    }

    public function testUpdateNoPK() {
        $this->expectExceptionMessage("Cannot update. Primary key column [id] is not set.");
        $this->expectException(Exception::class);
        $person = new Person();
        $person->update();
    }

    public function testUpdateNoPKComposite1() {
        $this->expectExceptionMessage("Cannot update. Primary key column [tradeno] is not set.");
        $this->expectException(Exception::class);
        $trade = new Trade();
        $trade->tradedate = date('Y-m-d');
        $trade->update();
    }

    public function testUpdateNoPKComposite2() {
        $this->expectExceptionMessage("Cannot update. Primary key column [tradedate] is not set.");
        $this->expectException(Exception::class);
        $trade = new Trade();
        $trade->tradeno = 100;
        $trade->update();
    }

    public function testUpdatePkless() {
        $this->expectExceptionMessage("Primary key not defined for model [Phormium\Tests\Models\PkLess].");
        $this->expectException(Exception::class);
        $pkless = new PkLess();
        $pkless->update();
    }

    public function testDelete() {
        $person1 = new Person();
        $person1->name = 'Short Lived Person';
        $person1->save();

        $qs = Person::objects()->filter('id', '=', $person1->id);
        $person2 = $qs->single();

        $this->assertEquals($person1, $person2);
        $this->assertTrue($qs->exists());

        $count = $person1->delete();
        $this->assertFalse($qs->exists());
        $this->assertSame(1, $count);

        // On repeated delete, 0 count should be returned
        $count = $person1->delete();
        $this->assertSame(0, $count);
    }

    public function testSelectAndUpdate() {
        $person1 = new Person();
        $person1->name = 'Short Lived Person';
        $person1->save();

        $this->assertNotEmpty($person1->id);

        $update = Person::get($person1->id);
        $update->name = 'Long Lived Person';
        $update->save();

        $this->assertNotEmpty($person1->id);
    }

    public function testLimit() {
        $allPeople = Person::objects()
            ->orderBy('id', 'asc')
            ->fetch();

        $limit = 3;
        $offset = 2;

        $expected = array_slice($allPeople, $offset, $limit);
        $actual = Person::objects()
            ->orderBy('id', 'asc')
            ->limit($limit, $offset)
            ->fetch();

        $this->assertEquals($expected, $actual);
    }

    /**
     * Check single() fails when no records match.
     */
    public function testSingleZero() {
        $this->expectExceptionMessage("Query returned 0 rows. Requested a single row.");
        $this->expectException(Exception::class);
        $qs = Person::objects()->filter('name', '=', 'Hrvoje');
        $qs->delete();

        $this->assertSame(0, $qs->count());
        $this->assertFalse($qs->exists());

        Person::objects()->filter('name', '=', 'Hrvoje')->single();
    }

    public function testSingleZeroAllowed() {
        $qs = Person::objects()->filter('name', '=', 'The Invisible Man');
        $qs->delete();

        $this->assertSame(0, $qs->count());
        $this->assertFalse($qs->exists());

        $actual = $qs->single(true);
        $this->assertNull($actual);
    }

    /**
     * Check single() fails when multiple records match.
     */
    public function testSingleMultiple() {
        $this->expectExceptionMessage("Query returned 3 rows. Requested a single row.");
        $this->expectException(Exception::class);
        $qs = Person::objects()->filter('name', '=', 'Hrvoje');
        $qs->delete();

        $this->assertSame(0, $qs->count());
        $this->assertFalse($qs->exists());

        $data = ['name' => 'Hrvoje'];
        Person::fromArray($data)->save();
        Person::fromArray($data)->save();
        Person::fromArray($data)->save();

        $this->assertSame(3, $qs->count());
        $this->assertTrue($qs->exists());

        $qs->single();
    }

    /**
     * Method fromArray() should also handle stdClass objects.
     */
    public function testFromObject() {
        $array = ['name' => 'Kiki', 'income' => 123.45];
        $object = (object)$array;

        $p1 = Person::fromArray($array);
        $p2 = Person::fromArray($object);

        $this->assertEquals($p1, $p2);
    }

    /**
     * Get doesn't work on models without a primary key.
     */
    public function testGetErrorOnPKLess() {
        $this->expectExceptionMessage("Primary key not defined for model [Phormium\Tests\Models\PkLess].");
        $this->expectException(Exception::class);
        PkLess::get(1);
    }

    public function testGetErrorWrongPKCount() {
        $this->expectExceptionMessage("Model [Phormium\Tests\Models\Person] has 1 primary key columns. 3 arguments given.");
        $this->expectException(Exception::class);
        Person::get(1, 2, 3);
    }

    public function testGetErrorModelDoesNotExist() {
        $this->expectExceptionMessage("[Phormium\Tests\Models\Person] record with primary key [12345678] does not exist.");
        $this->expectException(ModelNotFoundException::class);
        Person::get(12345678);
    }

    public function testFind() {
        $this->assertNull(Person::find(12345678));

        $p = new Person();
        $p->name = "Jimmy Hendrix";
        $p->insert();

        $p2 = Person::find($p->id);
        $this->assertNotNull($p2);
        $this->assertEquals($p, $p2);
    }

    public function testExists() {
        $this->assertFalse(Person::exists(12345678));

        $p = new Person();
        $p->name = "Jimmy Page";
        $p->insert();

        $actual = Person::exists($p->id);
        $this->assertTrue($actual);
    }

    public function testGetPK() {
        $foo = new Person();
        $this->assertCount(1, $foo->getPK());

        $foo = new PkLess();
        $this->assertCount(0, $foo->getPK());

        $foo = new Trade();
        $this->assertCount(2, $foo->getPK());
    }

    public function testFetchDistinct() {
        $name = uniqid();

        Person::fromArray(['name' => $name, 'income' => 100])->insert();
        Person::fromArray(['name' => $name, 'income' => 100])->insert();
        Person::fromArray(['name' => $name, 'income' => 100])->insert();
        Person::fromArray(['name' => $name, 'income' => 200])->insert();
        Person::fromArray(['name' => $name, 'income' => 200])->insert();
        Person::fromArray(['name' => $name, 'income' => 200])->insert();

        $actual = Person::objects()
            ->filter('name', '=', $name)
            ->orderBy('income', 'asc')
            ->distinct('name', 'income');

        $expected = [
            [
                'name' => $name,
                'income' => 100,
            ],
            [
                'name' => $name,
                'income' => 200,
            ],
        ];
        $this->assertEquals($expected, $actual);

        $actual = Person::objects()
            ->filter('name', '=', $name)
            ->orderBy('income', 'asc')
            ->distinct('income');

        $expected = [100, 200];
        $this->assertEquals($expected, $actual);
    }

    public function testFetchDistinctFailureNoColumns() {
        $this->expectExceptionMessage("No columns given");
        $this->expectException(Exception::class);
        Person::objects()->distinct();
    }

    public function testFetchValues() {
        $name = uniqid();

        Person::fromArray(['name' => "$name-1", 'income' => 100])->insert();
        Person::fromArray(['name' => "$name-2", 'income' => 200])->insert();
        Person::fromArray(['name' => "$name-3", 'income' => 300])->insert();

        $actual = Person::objects()
            ->filter('name', 'LIKE', "$name%")
            ->orderBy('name', 'asc')
            ->values('name', 'income');

        $expected = [
            ['name' => "$name-1", 'income' => 100],
            ['name' => "$name-2", 'income' => 200],
            ['name' => "$name-3", 'income' => 300],
        ];

        $this->assertEquals($expected, $actual);

        $actual = Person::objects()
            ->filter('name', 'LIKE', "$name%")
            ->orderBy('name', 'asc')
            ->valuesList('name', 'income');

        $expected = [
            ["$name-1", 100],
            ["$name-2", 200],
            ["$name-3", 300],
        ];

        $this->assertEquals($expected, $actual);

        $actual = Person::objects()
            ->filter('name', 'LIKE', "$name%")
            ->orderBy('name', 'asc')
            ->valuesFlat('name');

        $expected = [
            "$name-1",
            "$name-2",
            "$name-3",
        ];

        $this->assertEquals($expected, $actual);
    }

    public function testToArray() {
        $person = Person::fromArray([
            'name' => "Michael Kiske",
            'email' => "miki@example.com",
            'income' => 100000,
        ]);

        $expected = [
            'id' => null,
            'name' => 'Michael Kiske',
            'email' => 'miki@example.com',
            'birthday' => null,
            'created' => null,
            'income' => 100000,
            'is_cool' => null,
        ];

        $this->assertSame($expected, $person->toArray());
    }

    public function testToJson() {
        $person = Person::fromArray([
            'name' => "Michael Kiske",
            'email' => "miki@example.com",
            'income' => 100000,
        ]);

        $expected = '{"id":null,"name":"Michael Kiske","email":"miki@example.com","birthday":null,"created":null,"income":100000,"is_cool":null}';

        $this->assertSame($expected, $person->toJSON());
    }

    public function testToYaml() {
        $person = Person::fromArray([
            'name' => "Michael Kiske",
            'email' => "miki@example.com",
            'income' => 100000,
        ]);

        $expected = implode("\n", [
                'id: null',
                "name: 'Michael Kiske'",
                'email: miki@example.com',
                'birthday: null',
                'created: null',
                'income: 100000',
                'is_cool: null',
            ]) . "\n";

        $this->assertSame($expected, $person->toYAML());
    }

    public function testSaveModelWithoutPrimaryKey() {
        $this->expectExceptionMessage("Model not writable because primary key is not defined in _meta.");
        $this->expectException(Exception::class);
        $pkl = new PkLess();
        $pkl->save();
    }

    public function testAll() {
        Contact::objects()->delete();
        Asset::objects()->delete();
        Person::objects()->delete();

        $actual = Person::all();
        $this->assertIsArray($actual);
        $this->assertEmpty($actual);

        Person::fromArray(['name' => "Freddy Mercury"])->insert();
        Person::fromArray(['name' => "Brian May"])->insert();
        Person::fromArray(['name' => "Roger Taylor"])->insert();

        $actual = Person::all();
        $this->assertIsArray($actual);
        $this->assertCount(3, $actual);
    }

    public function testDump() {
        $p = Person::fromArray([
            'id' => 10,
            'name' => "Tom Lehrer",
            'email' => "tom@lehrer.net",
            'birthday' => "1928-04-09",
            'income' => 1000
        ]);

        ob_start();
        $p->dump();
        $actual = ob_get_clean();

        $expected = implode("\n", [
            'Phormium\Tests\Models\Person (testdb.person)',
            '============================================',
            'id: 10 (PK)',
            'name: "Tom Lehrer"',
            'email: "tom@lehrer.net"',
            'birthday: "1928-04-09"',
            'created: NULL',
            'income: 1000',
            'is_cool: NULL',
        ]);
        $expected .= "\n\n";

        $this->assertSame($expected, $actual);
    }

    public function testGetMeta() {
        // Just to improve code coverage
        $meta1 = Person::getMeta();
        $meta2 = Person::objects()->getMeta();

        $this->assertSame($meta1, $meta2);
    }
}
