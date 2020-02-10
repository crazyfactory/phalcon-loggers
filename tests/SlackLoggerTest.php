<?php
/**
 * Created by PhpStorm.
 * User: p.petpadriew
 * Date: 2/10/2020
 * Time: 4:48 PM
 */

namespace CrazyFactory\PhalconLogger\Test;


use CrazyFactory\PhalconLogger\SlackLogger;
use CrazyFactory\Slack\SlackClient;
use Phalcon\Config;
use Phalcon\Logger;

class SlackLoggerTest extends TestCase
{
    public function testSkipLog()
    {
        $config = new Config([
            'slack' => [
                'level' => Logger::WARNING
            ]
        ]);

        $slackClient = \Mockery::mock(SlackClient::class);
        // Should not call when logger if minimum log level not reach
        $slackClient->shouldNotHaveReceived('send');
        $slackLogger = new SlackLogger($config,$slackClient);

        $slackLogger->info('test');
    }

    public function testOverrideChannel()
    {
        // Should override channel and append text when logging level reaches
        $config = new Config([
            'slack' => [
                'level' => Logger::DEBUG,
                'context' => [
                    'channel' => '#first-channel',
                ],
                'alert_channel' => '#alert-channel',
            ]
        ]);

        $slackClient = \Mockery::mock(SlackClient::class);
        $slackClient->shouldReceive('send')
        ->with(['channel' => '#alert-channel', 'text' => "[ERROR] test\n"]);
        $slackLogger = new SlackLogger($config,$slackClient);
        $slackLogger->error('test');

        // Should not override when logging level not reach
        $slackClient = \Mockery::mock(SlackClient::class);
        $slackClient->shouldReceive('send')
            ->with(['text' => "[INFO] test\n"]);
        $slackLogger = new SlackLogger($config,$slackClient);
        $slackLogger->info('test');
    }

}
