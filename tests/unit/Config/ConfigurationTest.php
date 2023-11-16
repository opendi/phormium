<?php

namespace Phormium\Tests\Unit\Config;

use Phormium\Config\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

/**
 * @group config
 * @group unit
 */
class ConfigurationTest extends TestCase {
    public function testConfiguration() {
        $conf = new Configuration();
        $builder = $conf->getConfigTreeBuilder();

        $expected = TreeBuilder::class;
        $this->assertInstanceOf($expected, $builder);
    }
}
