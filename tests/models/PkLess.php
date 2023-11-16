<?php

namespace Phormium\Tests\Models;

use Phormium\Model;

/**
 * A model with no primary key.
 */
class PkLess extends Model {
    protected static $_meta = [
        'database' => 'testdb',
        'table' => 'pkless',
    ];

    public $foo;
    public $bar;
    public $baz;
}
