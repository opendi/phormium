<?php

namespace Phormium\Tests\Integration;

use Phormium\Orm;
use Phormium\Printer;
use Phormium\Tests\Models\Person;
use PHPUnit\Framework\TestCase;

/**
 * @group printer
 */
class PrinterTest extends TestCase {
    public static function setUpBeforeClass(): void {
        Orm::configure(PHORMIUM_CONFIG_FILE);
    }

    public function testDumpQSReturn() {
        $name = "Freddy Mercury";

        Person::objects()->filter("name", "=", $name)->delete();

        $person1 = Person::fromArray(["name" => $name, "income" => 100]);
        $person2 = Person::fromArray(["name" => $name, "income" => 200]);
        $person3 = Person::fromArray(["name" => $name, "income" => 300]);

        $person1->save();
        $id1 = $person1->id;
        $person2->save();
        $id2 = $person2->id;
        $person3->save();
        $id3 = $person3->id;

        $actual = Person::objects()->filter("name", "=", $name)->dump(true);
        $lines = explode(PHP_EOL, $actual);

        $this->assertMatchesRegularExpression("/^\\s*id\\s+name\\s+email\\s+birthday\\s+created\\s+income\\s+is_cool\\s*$/", $lines[0]);
        $this->assertMatchesRegularExpression("/^=+$/", $lines[1]);
        $this->assertMatchesRegularExpression("/^\\s*$id1\\s+Freddy Mercury\\s+100(.00)?\\s*$/", $lines[2]);
        $this->assertMatchesRegularExpression("/^\\s*$id2\\s+Freddy Mercury\\s+200(.00)?\\s*$/", $lines[3]);
        $this->assertMatchesRegularExpression("/^\\s*$id3\\s+Freddy Mercury\\s+300(.00)?\\s*$/", $lines[4]);
    }

    public function testDumpArrayReturn() {
        $name = "Freddy Mercury";

        $data = [
            ["id" => 1, "name" => $name, "email" => "freddy@queen.org", "income" => 100],
            ["id" => 2, "name" => $name, "email" => "freddy@queen.org", "income" => 200],
            ["id" => 3, "name" => $name, "email" => "freddy@queen.org", "income" => 300],
        ];

        $printer = new Printer();
        $actual = $printer->dump($data, true);
        $lines = explode(PHP_EOL, $actual);

        $this->assertMatchesRegularExpression("/^\\s*id\\s+name\\s+email\\s+income\\s*$/", $lines[0]);
        $this->assertMatchesRegularExpression("/^=+$/", $lines[1]);
        $this->assertMatchesRegularExpression("/^\\s*1\\s+Freddy Mercury\\s+freddy@queen.org\\s+100(.00)?\\s*$/", $lines[2]);
        $this->assertMatchesRegularExpression("/^\\s*2\\s+Freddy Mercury\\s+freddy@queen.org\\s+200(.00)?\\s*$/", $lines[3]);
        $this->assertMatchesRegularExpression("/^\\s*3\\s+Freddy Mercury\\s+freddy@queen.org\\s+300(.00)?\\s*$/", $lines[4]);
    }

    public function testDumpEcho() {
        $name = "Rob Halford";

        Person::objects()->filter("name", "=", $name)->delete();

        $person1 = Person::fromArray(["name" => $name, "income" => 100]);
        $person2 = Person::fromArray(["name" => $name, "income" => 200]);
        $person3 = Person::fromArray(["name" => $name, "income" => 300]);

        $person1->save();
        $id1 = $person1->id;
        $person2->save();
        $id2 = $person2->id;
        $person3->save();
        $id3 = $person3->id;

        ob_start();
        Person::objects()->filter("name", "=", $name)->dump();
        $actual = ob_get_clean();

        $lines = explode(PHP_EOL, $actual);

        $this->assertMatchesRegularExpression("/^\\s*id\\s+name\\s+email\\s+birthday\\s+created\\s+income\\s+is_cool\\s*$/", $lines[0]);
        $this->assertMatchesRegularExpression("/^=+$/", $lines[1]);
        $this->assertMatchesRegularExpression("/^\\s*$id1\\s+Rob Halford\\s+100(.00)?\\s*$/", $lines[2]);
        $this->assertMatchesRegularExpression("/^\\s*$id2\\s+Rob Halford\\s+200(.00)?\\s*$/", $lines[3]);
        $this->assertMatchesRegularExpression("/^\\s*$id3\\s+Rob Halford\\s+300(.00)?\\s*$/", $lines[4]);
    }

    public function testDumpEchoEmptyQS() {
        $name = "Rob Halford";

        Person::objects()->filter("name", "=", $name)->delete();

        ob_start();
        Person::objects()->filter("name", "=", $name)->dump();
        $actual = ob_get_clean();

        $this->assertSame("", $actual);
    }

    public function testDumpEchoEmptyArray() {
        ob_start();
        $printer = new Printer();
        $printer->dump([]);
        $actual = ob_get_clean();

        $this->assertSame("", $actual);
    }
}