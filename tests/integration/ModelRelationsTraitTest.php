<?php

namespace Phormium\Tests\Integration;

use Phormium\Exception\InvalidRelationException;
use Phormium\Orm;
use Phormium\QuerySet;
use Phormium\Tests\Models\Asset;
use Phormium\Tests\Models\Contact;
use Phormium\Tests\Models\Person;
use PHPUnit\Framework\TestCase;

/**
 * @group model
 */
class ModelRelationsTraitTest extends TestCase {
    private static $person;

    public static function setUpBeforeClass(): void {
        Orm::configure(PHORMIUM_CONFIG_FILE);

        self::$person = Person::fromArray(['name' => 'Udo Dirkschneider']);
        self::$person->save();
    }

    public function testGuessableRelation() {
        $pid = self::$person->id;

        // Contacts are linked to person via a guessable foreign key name
        // (person_id)
        $c1 = Contact::fromArray(['person_id' => $pid, "value" => "Contact #1"]);
        $c2 = Contact::fromArray(['person_id' => $pid, "value" => "Contact #2"]);
        $c3 = Contact::fromArray(['person_id' => $pid, "value" => "Contact #3"]);

        $c1->save();
        $c2->save();
        $c3->save();

        $contacts = self::$person->hasChildren(Contact::class);
        $this->assertInstanceOf(QuerySet::class, $contacts);

        $actual = $contacts->fetch();
        $expected = [$c1, $c2, $c3];
        $this->assertEquals($expected, $actual);

        $p1 = $c1->hasParent(Person::class)->single();
        $p2 = $c2->hasParent(Person::class)->single();
        $p3 = $c3->hasParent(Person::class)->single();

        $this->assertEquals(self::$person, $p1);
        $this->assertEquals(self::$person, $p2);
        $this->assertEquals(self::$person, $p3);
    }

    public function testUnguessableRelation() {
        $pid = self::$person->id;

        // Asset is similar to contact, but has a non-guessable foreign key name
        // (owner_id)
        $a1 = Asset::fromArray(['owner_id' => $pid, "value" => "Asset #1"]);
        $a2 = Asset::fromArray(['owner_id' => $pid, "value" => "Asset #2"]);
        $a3 = Asset::fromArray(['owner_id' => $pid, "value" => "Asset #3"]);

        $a1->save();
        $a2->save();
        $a3->save();

        $assets = self::$person->hasChildren(Asset::class, "owner_id");
        $this->assertInstanceOf(QuerySet::class, $assets);

        $actual = $assets->fetch();
        $expected = [$a1, $a2, $a3];
        $this->assertEquals($expected, $actual);

        $p1 = $a1->hasParent(Person::class, "owner_id")->single();
        $p2 = $a2->hasParent(Person::class, "owner_id")->single();
        $p3 = $a3->hasParent(Person::class, "owner_id")->single();

        $this->assertEquals(self::$person, $p1);
        $this->assertEquals(self::$person, $p2);
        $this->assertEquals(self::$person, $p3);
    }

    public function testInvalidModel1() {
        $this->expectExceptionMessage("Model class \"foo\" does not exist");
        $this->expectException(InvalidRelationException::class);
        // Class does not exist
        self::$person->hasChildren("foo");
    }

    public function testInvalidModel2() {
        $this->expectExceptionMessage("Given class \"DateTime\" is not a subclass of Phormium\Model");
        $this->expectException(InvalidRelationException::class);
        // Class exists but is not a model
        self::$person->hasChildren("DateTime");
    }

    public function testInvalidKey1() {
        $this->expectExceptionMessage("Empty key given");
        $this->expectException(InvalidRelationException::class);
        // Empty key
        self::$person->hasChildren(Contact::class, []);
    }

    public function testInvalidKey2() {
        $this->expectExceptionMessage("Invalid key type: \"object\". Expected string or array.");
        $this->expectException(InvalidRelationException::class);
        // Key is a class instead of string or array
        self::$person->hasChildren(Contact::class, new Contact());
    }

    public function testInvalidKey3() {
        $this->expectExceptionMessage("Property \"foo\" does not exist");
        $this->expectException(InvalidRelationException::class);
        // Property does not exist
        self::$person->hasChildren(Contact::class, "foo");
    }
}
