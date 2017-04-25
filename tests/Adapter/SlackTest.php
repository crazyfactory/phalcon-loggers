<?php

namespace CrazyFactory\PhalconLogger\Test\Adapter;

use CrazyFactory\PhalconLogger\Adapter\Slack;
use CrazyFactory\PhalconLogger\Test\TestCase;
use Phalcon\Config;
use Phalcon\Logger;

class SlackTest extends TestCase
{
    public function test_construct_accepts_config_array()
    {
        $config = require __DIR__ . '/../fixtures/config.logger.php';

        $this->assertTrue(is_array($config));

        new Slack($config);
    }

    public function test_construct_accepts_config_object()
    {
        $config = new Config(require __DIR__ . '/../fixtures/config.logger.php');

        $this->assertTrue(is_object($config));
        $this->assertInstanceOf(Config::class, $config);

        new Slack($config);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function test_construct_throws_on_invalid_config()
    {
        $config = __DIR__ . '/../fixtures/config.logger.php';

        new Slack($config);
    }

    public function test_logInternal_doesnot_log_when_env_is_not_whitelisted()
    {
        $config                          = require __DIR__ . '/../fixtures/config.logger.php';
        $config['slack']['environments'] = [];

        ($slack = new Slack($config))->logInternal(__METHOD__, Logger::SPECIAL, time());

        $this->assertSame(0, $slack->getEventCount(), 'nothing_should_have_been_logged');
    }

    public function test_logInternal_doesnot_log_when_webhook_not_set()
    {
        $config = require __DIR__ . '/../fixtures/config.logger.php';

        $this->assertEmpty($config['slack']['webhookUrl'], 'webhookUrl_not_set');

        ($slack = new Slack($config))->logInternal(__METHOD__, Logger::SPECIAL, time());

        $this->assertSame(0, $slack->getEventCount(), 'nothing_should_have_been_logged');
    }

    public function test_logInternal_doesnot_log_unspecified_levels()
    {
        $config                    = require __DIR__ . '/../fixtures/config.logger.php';
        $config['slack']['levels'] = [Logger::ERROR];

        ($slack = new Slack($config))->logInternal(__METHOD__, Logger::INFO, time());

        $this->assertSame(0, $slack->getEventCount(), 'nothing_should_have_been_logged');
    }

    public function test_logInternal_logs_specified_levels()
    {
        $config                        = require __DIR__ . '/../fixtures/config.logger.php';
        $config['slack']['webhookUrl'] = 'dummy.url';
        $config['slack']['levels']     = [Logger::CRITICAL];

        ($slack = new Slack($config))->logInternal(__METHOD__, Logger::CRITICAL, time());
        $this->assertSame(1, $slack->getEventCount(), 'one_event_should_have_been_logged');
    }

    /**
     * @dataProvider preparePayloadTestcases
     */
    public function test_preparePayload(string $message, array $context, array $config, array $expected)
    {
        $payload = (new Slack($config))->preparePayload($message, $context);
        $this->assertEquals(json_encode($expected, JSON_UNESCAPED_UNICODE), $payload);
    }

    public function preparePayloadTestcases()
    {
        return [
            [
                'hello {name}, this is a test',
                ['mentions' => 'slackbot,test', 'name' => 'there', 'attachment' => [
                    'title' => 'A',
                    'text'  => 'apple',
                    'color' => 'good',
                ], 'type' => 8, 'time' => $t = time()],
                [
                    'environment' => 'test',
                    'appName'     => 'phalcon-loggers',
                    'slack'       => ['context' => ['channel' => '#random']],
                ],
                [
                    'channel' => '#random',
                    'text' => "*phalcon-loggers@test* <@slackbot> <@test>\nhello there, this is a test",
                    'attachments' => [[
                        'title'     => 'A',
                        'text'      => 'apple',
                        'color'     => 'good',
                        'fallback'  => 'hello there, this is a test',
                        'mrkdwn_in' => ['fields'],
                        'ts'        => $t,
                    ]],
                ],
            ],
            [
                'another test',
                ['mentions' => 'test'],
                [
                    'environment' => 'test',
                    'appName'     => '',
                    'slack'       => ['context' => ['channel' => '#general']],
                ],
                [
                    'channel' => '#general',
                    'text'    => " <@test>\nanother test",
                ],
            ],
            [
                'another test',
                [],
                [
                    'environment' => 'test',
                    'appName'     => '',
                    'slack'       => ['context' => ['mrkdwn' => false]],
                ],
                [
                    'mrkdwn' => false,
                    'text'   => "\nanother test",
                ],
            ],
        ];
    }
}
