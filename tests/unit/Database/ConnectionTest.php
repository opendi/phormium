<?php

namespace Phormium\Tests;

use Evenement\EventEmitter;

use Mockery as m;

use Phormium\Database\Database;
use Phormium\Database\Factory;
use Phormium\Event;

use PDO;

class ConnectionTest extends \PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }
}
