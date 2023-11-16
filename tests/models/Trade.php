<?php

namespace Phormium\Tests\Models;

use Phormium\Model;

/**
 * A stock exchange trade.
 * Demonstrates usage of composite primary key.
 */
class Trade extends Model {
    protected static $_meta = [
        'database' => 'testdb',
        'table' => 'trade',
        'pk' => ['tradedate', 'tradeno']
    ];

    public $tradedate;
    public $tradeno;
    public $price;
    public $quantity;
}
