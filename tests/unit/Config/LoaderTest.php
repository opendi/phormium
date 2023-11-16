<?php

namespace Phormium\Tests\Unit\Config;

use Phormium\Config\ArrayLoader;
use Phormium\Config\JsonLoader;
use Phormium\Config\YamlLoader;
use Phormium\Exception\ConfigurationException;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Yaml\Yaml;

/**
 * @group config
 * @group unit
 */
class LoaderTest extends TestCase {
    public function testArrayLoader() {
        $config = ['foo' => 'bar'];

        $loader = new ArrayLoader();

        $this->assertSame($config, $loader->load($config));

        $this->assertTrue($loader->supports([]));
        $this->assertFalse($loader->supports(""));
        $this->assertFalse($loader->supports(123));
        $this->assertFalse($loader->supports(new stdClass));
    }

    public function testJsonLoader() {
        $config = ['foo' => 'bar'];
        $json = @json_encode($config);

        $tempFile = tempnam(sys_get_temp_dir(), "pho") . ".json";
        file_put_contents($tempFile, $json);

        $loader = new JsonLoader();

        $this->assertSame($config, $loader->load($tempFile));

        $this->assertTrue($loader->supports("foo.json"));
        $this->assertFalse($loader->supports("foo.yaml"));
        $this->assertFalse($loader->supports(123));
        $this->assertFalse($loader->supports([]));
        $this->assertFalse($loader->supports(new stdClass));

        unlink($tempFile);
    }

    public function testJsonLoaderInvalidSyntax() {
        $this->expectExceptionMessage("Failed parsing JSON configuration file.");
        $this->expectException(ConfigurationException::class);
        $tempFile = tempnam(sys_get_temp_dir(), "pho") . ".json";
        file_put_contents($tempFile, "this is not json");

        $loader = new JsonLoader();
        $loader->load($tempFile);

        unlink($tempFile);
    }

    public function testYamlLoader() {
        $config = ['foo' => 'bar'];
        $yaml = Yaml::dump($config);

        $tempFile = tempnam(sys_get_temp_dir(), "pho") . ".yaml";
        file_put_contents($tempFile, $yaml);

        $loader = new YamlLoader();

        $this->assertSame($config, $loader->load($tempFile));

        $this->assertTrue($loader->supports("foo.yaml"));
        $this->assertFalse($loader->supports("foo.json"));
        $this->assertFalse($loader->supports(123));
        $this->assertFalse($loader->supports([]));
        $this->assertFalse($loader->supports(new stdClass));

        unlink($tempFile);
    }

    public function testLoadFileFailed() {
        $this->expectExceptionMessage("Config file not found at \"doesnotexist.yaml\".");
        $this->expectException(ConfigurationException::class);
        $loader = new YamlLoader();
        $loader->load("doesnotexist.yaml");
    }
}
