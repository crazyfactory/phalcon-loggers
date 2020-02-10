<?php

namespace CrazyFactory\PhalconLogger\Test;


use CrazyFactory\PhalconLogger\EchoLogger;
use Phalcon\Config;

class EchoLoggerTest extends TestCase
{
    public function testEcho()
    {
        $config = new Config([
            'echoLogger' => [
                'level' => \Phalcon\Logger::NOTICE,
            ],
        ]);
        $this->expectOutputRegex('/Test/');

        $echoLogger = new EchoLogger($config);
        $echoLogger->notice('Test');
    }

    public function testEchoWithLowerLevelLogging()
    {
        $config = new Config([
            'echoLogger' => [
                'level' => \Phalcon\Logger::NOTICE,
            ],
        ]);
        $this->expectOutputRegex('/Test/');

        $echoLogger = new EchoLogger($config);
        $echoLogger->alert('Test');
    }

    public function testEchoWithUpperLevelLogging()
    {
        $config = new Config([
            'echoLogger' => [
                'level' => \Phalcon\Logger::NOTICE,
            ],
        ]);
        // Should not output anything when minimum level should be NOTICE and incoming msg is lower level
        $this->expectOutputString('');

        $echoLogger = new EchoLogger($config);
        $echoLogger->info('Test');
    }
}
