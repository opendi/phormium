<?php

namespace Phormium\Tests\Unit;

use Phormium\Exception\InvalidModelException;
use Phormium\Meta;
use Phormium\MetaBuilder;
use Phormium\Tests\Models\InvalidModel1;
use Phormium\Tests\Models\InvalidModel2;
use Phormium\Tests\Models\Model1;
use Phormium\Tests\Models\Model2;
use Phormium\Tests\Models\NotModel;
use Phormium\Tests\Models\Person;
use Phormium\Tests\Models\PkLess;
use Phormium\Tests\Models\Trade;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * @group unit
 */
class MetaBuilderTest extends TestCase {
    public function testPersonMeta() {
        $table = 'person';
        $class = Person::class;
        $database = 'testdb';
        $columns = ['id', 'name', 'email', 'birthday', 'created', 'income', 'is_cool'];
        $pk = ['id'];
        $nonPK = ['name', 'email', 'birthday', 'created', 'income', 'is_cool'];

        $expected = new Meta($table, $database, $class, $columns, $pk, $nonPK);

        $builder = new MetaBuilder();
        $actual = $builder->build($class);
        $this->assertEquals($expected, $actual);
    }

    public function testTradeMeta() {
        $table = 'trade';
        $class = Trade::class;
        $database = 'testdb';
        $columns = ['tradedate', 'tradeno', 'price', 'quantity'];
        $pk = ['tradedate', 'tradeno'];
        $nonPK = ['price', 'quantity'];

        $expected = new Meta($table, $database, $class, $columns, $pk, $nonPK);

        $builder = new MetaBuilder();
        $actual = $builder->build($class);
        $this->assertEquals($expected, $actual);
    }

    public function testPkLessMeta() {
        $table = 'pkless';
        $class = PkLess::class;
        $database = 'testdb';
        $columns = ['foo', 'bar', 'baz'];
        $pk = null;
        $nonPK = ['foo', 'bar', 'baz'];

        $expected = new Meta($table, $database, $class, $columns, $pk, $nonPK);

        $builder = new MetaBuilder();
        $actual = $builder->build($class);
        $this->assertEquals($expected, $actual);
    }

    public function testParse1() {
        $builder = new MetaBuilder();
        $meta = $builder->build(Model1::class);

        $this->assertInstanceOf(Meta::class, $meta);
        $this->assertSame('model1', $meta->getTable());
        $this->assertSame('database1', $meta->getDatabase());
        $this->assertSame(['id', 'foo', 'bar', 'baz'], $meta->getColumns());
        $this->assertSame(Model1::class, $meta->getClass());
        $this->assertSame(['id'], $meta->getPkColumns());
        $this->assertSame(['foo', 'bar', 'baz'], $meta->getNonPkColumns());
    }

    public function testParse2() {
        $builder = new MetaBuilder();
        $meta = $builder->build(Model2::class);

        $this->assertInstanceOf(Meta::class, $meta);
        $this->assertSame('model2', $meta->getTable());
        $this->assertSame('database1', $meta->getDatabase());
        $this->assertSame(['foo', 'bar', 'baz'], $meta->getColumns());
        $this->assertSame(Model2::class, $meta->getClass());
        $this->assertSame(['foo'], $meta->getPkColumns());
        $this->assertSame(['bar', 'baz'], $meta->getNonPkColumns());
    }

    public function testInvalidClass1() {
        $this->expectExceptionMessage("Invalid model given");
        $this->expectException(InvalidModelException::class);
        $builder = new MetaBuilder();
        $builder->build(123);
    }

    public function testInvalidClass2() {
        $this->expectExceptionMessage("Class \"Some\Class\" does not exist.");
        $this->expectException(InvalidModelException::class);
        $builder = new MetaBuilder();
        $builder->build("Some\\Class");
    }

    public function testInvalidClass3() {
        $this->expectExceptionMessage("Class \"Phormium\Tests\Models\NotModel\" is not a subclass of Phormium\Model.");
        $this->expectException(InvalidModelException::class);
        $builder = new MetaBuilder();
        $builder->build(NotModel::class);
    }

    public function testParseErrorNotArray() {
        $this->expectExceptionMessage("Invalid Phormium\Tests\Models\InvalidModel1::\$_meta. Not an array.");
        $this->expectException(InvalidModelException::class);
        $builder = new MetaBuilder();
        $builder->build(InvalidModel1::class);
    }

    public function testParseNoColumns() {
        $this->expectExceptionMessage("Model Phormium\Tests\Models\InvalidModel2 has no defined columns (public properties).");
        $this->expectException(InvalidModelException::class);
        $builder = new MetaBuilder();
        $builder->build(InvalidModel2::class);
    }

    public function testParseErrorMissingDatabase() {
        $this->expectExceptionMessage("Invalid Some\Class::\$_meta. Missing \"database\".");
        $this->expectException(InvalidModelException::class);
        $class = 'Some\\Class';
        $meta = [];

        $builder = new MetaBuilder();
        $method = new ReflectionMethod($builder, 'getDatabase');
        $method->setAccessible(true);
        $method->invoke($builder, $class, $meta);
    }

    public function testParseErrorMissingTable() {
        $this->expectExceptionMessage("Invalid Some\Class::\$_meta. Missing \"table\".");
        $this->expectException(InvalidModelException::class);
        $class = 'Some\\Class';
        $meta = [];

        $builder = new MetaBuilder();
        $method = new ReflectionMethod($builder, 'getTable');
        $method->setAccessible(true);
        $method->invoke($builder, $class, $meta);
    }

    public function testGetPK() {
        $class = 'Some\\Class';
        $columns = ['id', 'foo'];

        $builder = new MetaBuilder();
        $method = new ReflectionMethod($builder, 'getPK');
        $method->setAccessible(true);

        $meta = ['pk' => 'foo'];
        $expected = ['foo'];
        $actual = $method->invoke($builder, $class, $meta, $columns);
        $this->assertSame($expected, $actual);

        $meta = ['pk' => ['foo']];
        $expected = ['foo'];
        $actual = $method->invoke($builder, $class, $meta, $columns);
        $this->assertSame($expected, $actual);

        $meta = ['pk' => []];
        $expected = [];
        $actual = $method->invoke($builder, $class, $meta, $columns);
        $this->assertSame($expected, $actual);

        $meta = [];
        $expected = ['id'];
        $actual = $method->invoke($builder, $class, $meta, $columns);
        $this->assertSame($expected, $actual);
    }

    public function testGetPKMissingColumn() {
        $this->expectExceptionMessage("Invalid Some\Class::\$_meta. Specified primary key column(s) do not exist: bar");
        $this->expectException(InvalidModelException::class);
        $class = 'Some\\Class';

        $builder = new MetaBuilder();
        $method = new ReflectionMethod($builder, 'getPK');
        $method->setAccessible(true);

        $columns = ['foo'];
        $meta = ['pk' => 'bar'];
        $method->invoke($builder, $class, $meta, $columns);
    }

    public function testGetPKInvalidPK() {
        $this->expectExceptionMessage("Invalid primary key given in Some\Class::\$_meta. Not a string or array.");
        $this->expectException(InvalidModelException::class);
        $class = 'Some\\Class';
        $columns = ['foo'];

        $builder = new MetaBuilder();
        $method = new ReflectionMethod($builder, 'getPK');
        $method->setAccessible(true);

        $meta = ['pk' => true];
        $expected = ['foo'];
        $method->invoke($builder, $class, $meta, $columns);
    }

    public function testGetColumnsNoColumns() {
        $this->expectExceptionMessage("Invalid primary key given in Some\Class::\$_meta. Not a string or array.");
        $this->expectException(InvalidModelException::class);
        $class = 'Some\\Class';
        $columns = ['foo'];

        $builder = new MetaBuilder();
        $method = new ReflectionMethod($builder, 'getPK');
        $method->setAccessible(true);

        $meta = ['pk' => true];
        $expected = ['foo'];
        $method->invoke($builder, $class, $meta, $columns);
    }
}
