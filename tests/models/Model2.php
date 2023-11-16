<?php

namespace Phormium\Tests\Models;

use Phormium\Model;

/**
 * A test model with an explicit primary key column.
 */
class Model2 extends Model {
    protected static $_meta = [
        'pk' => 'foo',
        'database' => 'database1',
        'table' => 'model2'
    ];

    public $foo;
    public $bar;
    public $baz;
}
