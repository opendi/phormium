<?php

namespace Phormium\Tests\Models;

use Phormium\Model;

/**
 * A test model with no public properties.
 */
class InvalidModel2 extends Model {
    protected static $_meta = [
        'database' => 'database1',
        'table' => 'invalid_model_2'
    ];
}
