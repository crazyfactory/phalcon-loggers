<?php

namespace CrazyFactory\PhalconLogger\Test;

use CrazyFactory\PhalconLogger\Service;
use Phalcon\Config;
use Phalcon\Di;
use Phalcon\Di\FactoryDefault\Cli as CliDI;

class ServiceTest extends TestCase
{
    public function test_register_with_defaults()
    {
        Di::setDefault(new CliDI);

        (new Service)->register();

        $this->assertTrue(Di::getDefault()->has('logger'));
        $this->assertTrue(Di::getDefault()->has('slack'));
        $this->assertTrue(Di::getDefault()->has('sentry'));
    }

    public function test_register_accepts_config_object()
    {
        (new Service)->register(new Config(require __DIR__ . '/fixtures/config.logger.php'), $di = new CliDI);

        $this->assertTrue($di->has('logger'));
        $this->assertTrue($di->has('slack'));
        $this->assertTrue($di->has('sentry'));
    }

    public function test_register_accepts_config_filepath()
    {
        $config = __DIR__ . '/fixtures/config.logger.php';

        $this->assertTrue(is_file($config));
        (new Service)->register($config, $di = new CliDI);

        $this->assertTrue($di->has('logger'));
        $this->assertTrue($di->has('slack'));
        $this->assertTrue($di->has('sentry'));
    }

    public function test_register_accepts_config_array()
    {
        $config = require __DIR__ . '/fixtures/config.logger.php';

        $this->assertTrue(is_array($config));
        (new Service)->register($config, $di = new CliDI);

        $this->assertTrue($di->has('logger'));
        $this->assertTrue($di->has('slack'));
        $this->assertTrue($di->has('sentry'));
    }

    public function test_register_takes_config_from_di_when_skipped()
    {
        $di     = new CliDI;
        $di->setShared('config', new Config(require __DIR__ . '/fixtures/config.logger.php'));

        (new Service)->register(null, $di);

        $this->assertTrue($di->has('logger'));
        $this->assertTrue($di->has('slack'));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function test_register_throws_on_invalid_config()
    {
        (new Service)->register(new \stdClass, new CliDI);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function test_register_throws_on_environment_not_set()
    {
        $config = require __DIR__ . '/fixtures/config.logger.php';
        unset($config['environment']);

        (new Service)->register($config);
    }

    public function test_setNames_can_turn_off_service_by_passing_false()
    {
        $config = __DIR__ . '/fixtures/config.logger.php';

        (new Service)->setNames(['slack' => false])->register($config, $di = new CliDI);

        $this->assertTrue($di->has('logger'));
        $this->assertFalse($di->has('slack'));
    }

    public function test_setNames_can_alias_service_names()
    {
        $config = __DIR__ . '/fixtures/config.logger.php';

        (new Service)->setNames(['logger' => 'loggr', 'slack' => 'slacker'])->register($config, $di = new CliDI);

        $this->assertFalse($di->has('logger'));
        $this->assertFalse($di->has('slack'));
        $this->assertTrue($di->has('loggr'));
        $this->assertTrue($di->has('slacker'));
    }
}
