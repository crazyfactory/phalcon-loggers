<?php

namespace CrazyFactory\PhalconLogger\Test;

use Mockery as m;
use CrazyFactory\PhalconLogger\Multiple;
use Phalcon\Logger;

class MultipleTest extends TestCase
{
    public function test_logException_calls_logException_on_each_handlers()
    {
        $multiple = new Multiple;
        $message  = 'test ' . rand();
        $handler  = m::mock('CrazyFactory\\PhalconLogger\\Adapter\\Sentry')
            ->shouldReceive('logException')->times(1)->andReturnUsing(function ($e) use ($message) {
                $this->assertSame($message, $e->getMessage());
            })
        ;

        $multiple->push($handler->getMock());

        $multiple->logException(new \Exception($message));
    }

    public function test_logException_calls_log_with_loglevel_error_when_logException_is_not_defined()
    {
        $multiple = new Multiple;
        $handler  = m::mock('CrazyFactory\\PhalconLogger\\Adapter\\Slack')
            ->shouldReceive('log')->times(1)->andReturnUsing(function ($type, $messageTemplate) {
                $this->assertSame(Logger::ERROR, $type);
                $this->assertSame('{class}#{code} with message \'{message}\' thrown at {file}:{line}', $messageTemplate);
            })
        ;

        $multiple->push($handler->getMock());

        $multiple->logException(new \Exception);
    }

    public function test_special_sets_log_level_special()
    {
        $multiple = new Multiple;
        $message  = 'test ' . rand();

        $handler = m::mock('Phalcon\\Logger\\Adapter')
            ->shouldReceive('log')->times(1)->andReturnUsing(function ($type) {
                $this->assertSame(Logger::SPECIAL, $type);
            })
        ;

        $multiple->push($handler->getMock());

        $multiple->special($message);
    }

    public function test_custom_sets_log_level_custom()
    {
        $multiple = new Multiple;
        $message  = 'test ' . rand();

        $handler = m::mock('Phalcon\\Logger\\Adapter')
            ->shouldReceive('log')->times(1)->andReturnUsing(function ($type) {
                $this->assertSame(Logger::CUSTOM, $type);
            })
        ;

        $multiple->push($handler->getMock());
        $multiple->custom($message);
    }

    public function test_setRequestId_calls_setRequestId_on_each_handlers()
    {
        $multiple  = new Multiple;
        $message   = 'test ' . rand();
        $requestId = 'req' . rand();

        $handler = m::mock('CrazyFactory\\PhalconLogger\\Adapter\\Sentry');
        $handler->shouldReceive('setRequestId')->times(1)
            ->andReturnUsing(function ($id) use ($requestId, $handler) {
                $this->assertSame($id, $requestId);

                return $handler;
            })
        ;

        $multiple->push($handler);
        $return = $multiple->setRequestId($requestId);

        $this->assertSame($return, $multiple);
    }
}
