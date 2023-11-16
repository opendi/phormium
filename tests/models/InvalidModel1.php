<?php

namespace Phormium\Tests\Models;

use Phormium\Model;

/**
 * A test model with invalid metadata.
 */
class InvalidModel1 extends Model {
    protected static $_meta = "foo";

    public $foo;
}
