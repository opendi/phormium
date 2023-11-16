<?php

namespace Phormium\Tests\Models;

use Phormium\Model;

/**
 * A test model with a default primary key column (id).
 */
class Model1 extends Model {
    protected static $_meta = [
        'database' => 'database1',
        'table' => 'model1'
    ];

    public $id;
    public $foo;
    public $bar;
    public $baz;
}
