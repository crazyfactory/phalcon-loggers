<?php

namespace CrazyFactory\PhalconLogger\Test\Adapter;

use Mockery as m;
use CrazyFactory\PhalconLogger\Adapter\Sentry;
use CrazyFactory\PhalconLogger\Test\TestCase;
use Phalcon\Config;
use Phalcon\Logger;

class SentryTest extends TestCase
{
    public function test_construct_accepts_config_array()
    {
        $config = require __DIR__ . '/../fixtures/config.logger.php';

        $this->assertTrue(is_array($config));

        new Sentry($config);
    }

    public function test_construct_accepts_config_object()
    {
        $config = new Config(require __DIR__ . '/../fixtures/config.logger.php');

        $this->assertTrue(is_object($config));
        $this->assertInstanceOf(Config::class, $config);

        new Sentry($config);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function test_construct_throws_on_invalid_config()
    {
        $config = __DIR__ . '/../fixtures/config.logger.php';

        new Sentry($config);
    }

    public function test_toPhalconLogLevel()
    {
        $this->assertNull(Sentry::toPhalconLogLevel('undefined'), 'should_give_null_for_undefined_map');
        $this->assertTrue(is_int(Sentry::toPhalconLogLevel(\Raven_Client::ERROR)), 'should_give_int_for_defined_map');
    }

    public function test_toSentryLogLevel()
    {
        $this->assertNull(Sentry::toSentryLogLevel(-1), 'should_give_null_for_undefined_map');
        $this->assertTrue(is_string(Sentry::toSentryLogLevel(Logger::ERROR)), 'should_give_string_for_defined_map');
    }

    public function test_logInternal_doesnot_log_when_env_is_not_whitelisted()
    {
        $config = require __DIR__ . '/../fixtures/config.logger.php';
        $config['sentry']['environments'] = [];

        $client = m::spy('Raven_Client');
        ($sentry = new Sentry($config))->logInternal('message from ' . __METHOD__, Logger::INFO, time());

        $client->shouldNotHaveReceived('captureMessage');
    }

    public function test_logInternal_doesnot_log_when_client_not_set()
    {
        $config = require __DIR__ . '/../fixtures/config.logger.php';

        $client = m::spy('Raven_Client');
        ($sentry = new Sentry($config))->logInternal('message from ' . __METHOD__, Logger::INFO, time());

        $this->assertNull($sentry->getClient(), 'client_should_not_be_set');

        $client->shouldNotHaveReceived('captureMessage');
    }

    public function test_logInternal_doesnot_log_unspecified_levels()
    {
        $client = m::spy('Raven_Client');

        $config = require __DIR__ . '/../fixtures/config.logger.php';
        $config['sentry']['levels'] = [Logger::ERROR];

        ($sentry = new Sentry($config))->setClient($client)->logInternal(__METHOD__, Logger::INFO, time());

        $this->assertInstanceOf(\Raven_Client::class, $sentry->getClient(), 'client_should_be_set');

        $client->shouldNotHaveReceived('captureMessage');
    }

    public function test_logInternal_logs_specified_levels()
    {
        $client = m::spy('Raven_Client');

        $config = require __DIR__ . '/../fixtures/config.logger.php';
        $config['sentry']['levels'] = [Logger::CRITICAL];

        ($sentry = new Sentry($config))->setClient($client)->logInternal(__METHOD__, Logger::CRITICAL, time());

        $this->assertInstanceOf(\Raven_Client::class, $sentry->getClient(), 'client_should_be_set');

        $client->shouldHaveReceived('captureMessage');
    }

    public function test_logException_doesnt_log_ignored_exceptions()
    {
        $client = m::spy('Raven_Client');

        $config = require __DIR__ . '/../fixtures/config.logger.php';
        $config['sentry']['dontReport'] = [IgnoredException::class];

        ($sentry = new Sentry($config))->setClient($client)->logException(new IgnoredException, [], Logger::CRITICAL);

        $client->shouldNotHaveReceived('captureException');
    }

    public function test_logException_logs_unignored_exceptions()
    {
        $client = m::spy('Raven_Client');

        $config = require __DIR__ . '/../fixtures/config.logger.php';
        $config['sentry']['dontReport'] = [];

        ($sentry = new Sentry($config))->setClient($client)->logException(new IgnoredException, [], Logger::CRITICAL);

        $client->shouldHaveReceived('captureException');
    }

    public function test_setUserContext_sets_user_context_in_client()
    {
        $client = m::spy('Raven_Client');
        $config = require __DIR__ . '/../fixtures/config.logger.php';

        (new Sentry($config))->setClient($client)->setUserContext($context = ['id' => 1, 'email' => 'test@example.com']);

        $client->shouldHaveReceived('user_context', [$context]);
    }

    public function test_setExtraContext_sets_extra_context_in_client()
    {
        $client = m::spy('Raven_Client');
        $config = require __DIR__ . '/../fixtures/config.logger.php';

        (new Sentry($config))->setClient($client)->setExtraContext($context = ['key1' => 1, 'key2' => 'two']);

        $client->shouldHaveReceived('extra_context', [$context]);
    }

    public function test_setTag_sets_tag_in_client()
    {
        $client = m::spy('Raven_Client');
        $config = require __DIR__ . '/../fixtures/config.logger.php';

        (new Sentry($config))->setClient($client)->setTag('name', 'value');

        $client->shouldHaveReceived('tags_context', [['name' => 'value']]);
    }

    public function test_addCrumb_adds_breadcrumb_in_client()
    {
        $client = new RavenClient;
        $client->breadcrumbs = m::spy('Raven_Breadcrumbs');
        $config = require __DIR__ . '/../fixtures/config.logger.php';

        (new Sentry($config))->setClient($client)->addCrumb('hello');

        $client->breadcrumbs->shouldHaveReceived('record');
    }

    public function test_getLastEventId()
    {
        $client = new RavenClient;
        $config = require __DIR__ . '/../fixtures/config.logger.php';

        $sentry = (new Sentry($config))->setClient($client);

        $this->assertNull($sentry->getLastEventId(), 'last_event_id_should_be_null_initially');

        // Send a log to sentry and check last event ID again.
        $sentry->logInternal(__METHOD__, Logger::ERROR, time());

        $lastId = $sentry->getLastEventId();
        $this->assertNotEmpty($lastId);
        $this->assertTrue(is_string($lastId));
    }

    public function test_setClient_getClient()
    {
        $client = new RavenClient;
        $config = require __DIR__ . '/../fixtures/config.logger.php';
        $sentry = new Sentry($config);

        $this->assertNull($sentry->getClient(), 'client_should_be_null_initially');

        $sentry->setClient($client);

        $this->assertNotNull($sentry->getClient(), 'client_should_have_been_set');
        $this->assertInstanceOf(\Raven_Client::class, $sentry->getClient(), 'client_should_be_instanceof_Raven_Client');
    }

    public function test_setRequestId_tags_the_logs()
    {
        $client = m::spy('Raven_Client');
        $config = require __DIR__ . '/../fixtures/config.logger.php';

        $sentry = (new Sentry($config))->setClient($client)->setRequestId($request = 'req' . rand());
        $sentry->logInternal(__METHOD__, Logger::ERROR, time());

        $client->shouldHaveReceived('tags_context', [compact('request')]);
    }
}

class IgnoredException extends \Exception
{
}

// A stub class to prevent side effects of shut down handler of \Raven_Client.
class RavenClient extends \Raven_Client
{
    public $store_errors_for_bulk_send = true;

    public function onShutdown()
    {
        // Do nothing.
    }
}
