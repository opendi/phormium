<?php

namespace Phormium\Tests\Models;

use Phormium\Model;

class Person extends Model {
    protected static $_meta = [
        'database' => 'testdb',
        'table' => 'person',
        'pk' => 'id'
    ];

    public $id;
    public $name;
    public $email;
    public $birthday;
    public $created;
    public $income;
    public $is_cool;
}
