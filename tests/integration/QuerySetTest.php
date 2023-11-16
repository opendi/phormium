<?php

namespace Phormium\Tests\Integration;

use Exception;
use Phormium\Filter\ColumnFilter;
use Phormium\Filter\CompositeFilter;
use Phormium\Filter\Filter;
use Phormium\Orm;
use Phormium\Query\OrderBy;
use Phormium\Tests\Models\Person;
use PHPUnit\Framework\TestCase;

/**
 * @group queryset
 */
class QuerySetTest extends TestCase {
    public static function setUpBeforeClass(): void {
        Orm::configure(PHORMIUM_CONFIG_FILE);
    }

    public function testCloneQS() {
        $qs1 = Person::objects();
        $qs2 = $qs1->all();

        $this->assertEquals($qs1, $qs2);
        $this->assertNotSame($qs1, $qs2);
        $this->assertNull($qs1->getFilter());
        $this->assertNull($qs2->getFilter());
        $this->assertEmpty($qs1->getOrder());
        $this->assertEmpty($qs2->getOrder());
    }

    public function testDeepCloneQS() {
        $qs1 = Person::objects();
        $qs2 = $qs1->filter("1=1");
        $qs3 = $qs2->filter("1=2");

        $this->assertNull($qs1->getFilter());
        $this->assertNotNull($qs2->getFilter());
        $this->assertNotNull($qs3->getFilter());

        // Check that a deep clone has been made
        $this->assertNotSame($qs2->getFilter(), $qs3->getFilter());
    }

    public function testFilterQS() {
        $f = new ColumnFilter('name', '=', 'x');
        $qs1 = Person::objects();
        $qs2 = $qs1->filter('name', '=', 'x');

        $this->assertNotEquals($qs1, $qs2);
        $this->assertNotSame($qs1, $qs2);

        $this->assertInstanceOf(CompositeFilter::class, $qs2->getFilter());
        $this->assertCount(1, $qs2->getFilter()->filters());

        $this->assertEmpty($qs1->getOrder());
        $this->assertEmpty($qs2->getOrder());

        $expected = Filter::_and($f);
        $actual = $qs2->getFilter();
        $this->assertEquals($expected, $actual);
    }

    public function testFilterInvalidColumn() {
        $this->expectExceptionMessage("Invalid filter: Column [x] does not exist in table [person].");
        $this->expectException(Exception::class);
        Person::objects()->filter('x', '=', 'x');
    }

    public function testFilterInvalidOperation() {
        $this->expectExceptionMessage("Unknown filter operation [!!!].");
        $this->expectException(Exception::class);
        Person::objects()->filter('name', '!!!', 'x')->fetch();
    }

    public function testOrderQS() {
        $qs1 = Person::objects();
        $qs2 = $qs1->orderBy('name', 'desc');

        $this->assertNotEquals($qs1, $qs2);
        $this->assertNotSame($qs1, $qs2);

        $this->assertNull($qs1->getFilter());
        $this->assertNull($qs2->getFilter());
        $this->assertNull($qs1->getOrder());

        $orderBy2 = $qs2->getOrder();
        $columnOrder = $orderBy2->orders()[0];

        $this->assertInstanceOf(OrderBy::class, $orderBy2);
        $this->assertCount(1, $orderBy2->orders());
        $this->assertSame("name", $columnOrder->column());
        $this->assertSame("desc", $columnOrder->direction());

        $qs3 = $qs2->orderBy('id');
        $orderBy3 = $qs3->getOrder();
        $columnOrder1 = $orderBy3->orders()[0];
        $columnOrder2 = $orderBy3->orders()[1];

        $this->assertInstanceOf(OrderBy::class, $orderBy3);
        $this->assertNotSame($orderBy3, $orderBy2);
        $this->assertCount(2, $orderBy3->orders());
        $this->assertSame("name", $columnOrder1->column());
        $this->assertSame("desc", $columnOrder1->direction());
        $this->assertSame("id", $columnOrder2->column());
        $this->assertSame("asc", $columnOrder2->direction());
    }

    public function testOrderInvalidDirection() {
        $this->expectExceptionMessage("Invalid \$direction [!!!]. Expected one of [asc, desc].");
        $this->expectException(Exception::class);
        Person::objects()->orderBy('name', '!!!');
    }

    public function testOrderInvalidColumn() {
        $this->expectExceptionMessage("Cannot order by column [xxx] because it does not exist in table [person].");
        $this->expectException(Exception::class);
        Person::objects()->orderBy('xxx', 'asc');
    }

    public function testBatch() {
        // Create some sample data
        $uniq = uniqid('batch');

        $p1 = [
            'name' => "{$uniq}_1",
            'income' => 10000
        ];

        $p2 = [
            'name' => "{$uniq}_2",
            'income' => 20000
        ];

        $p3 = [
            'name' => "{$uniq}_3",
            'income' => 30000
        ];

        $qs = Person::objects()->filter('name', 'like', "{$uniq}%");

        $this->assertFalse($qs->exists());
        $this->assertSame(0, $qs->count());

        Person::fromArray($p1)->save();
        Person::fromArray($p2)->save();
        Person::fromArray($p3)->save();

        $this->assertTrue($qs->exists());
        $this->assertSame(3, $qs->count());

        // Give everybody a raise!
        $count = $qs->update(
            [
                'income' => 5000
            ]
        );

        $this->assertSame(3, $count);

        $persons = $qs->fetch();
        foreach ($persons as $person) {
            $this->assertEquals(5000, $person->income);
        }

        // Delete
        $count = $qs->delete();
        $this->assertSame(3, $count);

        // Check deleted
        $this->assertFalse($qs->exists());
        $this->assertSame(0, $qs->count());

        // Repeated delete should yield 0 count
        $count = $qs->delete();
        $this->assertSame(0, $count);
    }

    public function testLazyFetch() {
        $uniq = uniqid('lazy');

        $persons = [
            Person::fromArray(['name' => "{$uniq}_1"]),
            Person::fromArray(['name' => "{$uniq}_2"]),
            Person::fromArray(['name' => "{$uniq}_3"]),
            Person::fromArray(['name' => "{$uniq}_4"]),
            Person::fromArray(['name' => "{$uniq}_5"]),
        ];

        foreach ($persons as $person) {
            $person->save();
        }

        $qs = Person::objects()
            ->filter('name', 'like', "{$uniq}%")
            ->orderBy('name');

        $this->assertSame(5, $qs->count());

        $counter = 0;
        foreach ($qs->fetchLazy() as $key => $person) {
            $this->assertEquals($persons[$key], $person);
            $counter += 1;
        }

        $this->assertSame(5, $counter);
    }

    public function testLimitedFetch() {
        // Create some sample data
        $uniq = uniqid('limit');

        $persons = [
            Person::fromArray(['name' => "{$uniq}_1"]),
            Person::fromArray(['name' => "{$uniq}_2"]),
            Person::fromArray(['name' => "{$uniq}_3"]),
            Person::fromArray(['name' => "{$uniq}_4"]),
            Person::fromArray(['name' => "{$uniq}_5"]),
        ];

        foreach ($persons as $person) {
            $person->save();
        }

        $qs = Person::objects()
            ->filter('name', 'like', "{$uniq}%")
            ->orderBy('name');

        $this->assertEquals(array_slice($persons, 0, 2), $qs->limit(2)->fetch());
        $this->assertEquals(array_slice($persons, 0, 2), $qs->limit(2, 0)->fetch());
        $this->assertEquals(array_slice($persons, 1, 2), $qs->limit(2, 1)->fetch());
        $this->assertEquals(array_slice($persons, 2, 2), $qs->limit(2, 2)->fetch());
        $this->assertEquals(array_slice($persons, 3, 2), $qs->limit(2, 3)->fetch());
        $this->assertEquals(array_slice($persons, 0, 1), $qs->limit(1)->fetch());
        $this->assertEquals(array_slice($persons, 0, 1), $qs->limit(1, 0)->fetch());
        $this->assertEquals(array_slice($persons, 1, 1), $qs->limit(1, 1)->fetch());
        $this->assertEquals(array_slice($persons, 2, 1), $qs->limit(1, 2)->fetch());
        $this->assertEquals(array_slice($persons, 3, 1), $qs->limit(1, 3)->fetch());
        $this->assertEquals(array_slice($persons, 4, 1), $qs->limit(1, 4)->fetch());
    }

    public function testLimitedFetchWrongLimit1() {
        $this->expectExceptionMessage("\$limit must be a positive integer or null");
        $this->expectException(Exception::class);
        Person::objects()->limit(1.1);
    }

    public function testLimitedFetchWrongLimit2() {
        $this->expectExceptionMessage("\$limit must be a positive integer or null");
        $this->expectException(Exception::class);
        Person::objects()->limit("a");
    }

    public function testLimitedFetchWrongOffset1() {
        $this->expectExceptionMessage("\$offset must be a positive integer or null");
        $this->expectException(Exception::class);
        Person::objects()->limit(1, 1.1);
    }

    public function testLimitedFetchWrongOffset2() {
        $this->expectExceptionMessage("\$offset must be a positive integer or null");
        $this->expectException(Exception::class);
        Person::objects()->limit(1, "a");
    }

    public function testLimitOffsetDistinctQuery() {
        $p1 = Person::fromArray(['name' => 'Foo Bar']);
        $p2 = Person::fromArray(['name' => 'Foo Bar']);
        $p3 = Person::fromArray(['name' => 'Foo Bar']);
        $p4 = Person::fromArray(['name' => 'Foo Bar']);
        $p5 = Person::fromArray(['name' => 'Foo Bar']);

        $p1->save();
        $p2->save();
        $p3->save();
        $p4->save();
        $p5->save();

        $ids = [$p1->id, $p2->id, $p3->id, $p4->id, $p5->id];

        $res = Person::objects()
            ->filter('id', 'in', $ids)
            ->orderBy('id', 'desc')
            ->limit(3, 1)
            ->distinct('id');

        $this->assertCount(3, $res);
        $this->assertSame($p4->id, $res[0]);
        $this->assertSame($p3->id, $res[1]);
        $this->assertSame($p2->id, $res[2]);
    }
}
