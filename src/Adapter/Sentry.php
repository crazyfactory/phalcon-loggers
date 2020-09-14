<?php

namespace Easyconn\PhalconLogger\Adapter;

use Easyconn\PhalconLogger\Formatter;
use Phalcon\Config;
use Phalcon\Logger;
use Phalcon\Logger\Item;
use Sentry\State\Scope as SentryScope;
use Sentry\Severity;

/**
 * The Sentry logger adapter for phalcon.
 */
class Sentry extends Logger\Adapter\AbstractAdapter
{
    // The map of Phalcon log levels to Sentry log levels. Throughout the application, we use only Phalcon levels.
    const LOG_LEVELS = [
        Logger::EMERGENCY => Severity::FATAL,
        Logger::CRITICAL  => Severity::FATAL,
        Logger::ALERT     => Severity::INFO,
        Logger::ERROR     => Severity::ERROR,
        Logger::WARNING   => Severity::WARNING,
        Logger::NOTICE    => Severity::DEBUG,
        Logger::INFO      => Severity::INFO,
        Logger::DEBUG     => Severity::DEBUG,
        Logger::CUSTOM    => Severity::INFO
    ];

    /** @var \Raven_Client */
    protected $client;

    /** @var string The sentry event ID from last request */
    protected $lastEventId;

    /** @var string The request ID for tagging sentry events */
    protected $requestId;

    /** @var Config */
    protected $config;

    /**
     * Instantiates new Sentry Adapter with given configuration.
     *
     * @param \Phalcon\Config|array $config
     */
    public function __construct($config)
    {
        if (is_array($config)) {
            $config = new Config($config);
        }

        if (!$config instanceof Config) {
            throw new \InvalidArgumentException('Configuration parameter must be an array or instance of ' . Config::class);
        }

        $this->config = $config;

        $this->initClient();
    }

    /**
     * @param string $level
     *
     * @return int|null
     */
    public static function toPhalconLogLevel(string $level)
    {
        return array_flip(static::LOG_LEVELS)[$level] ?? null;
    }

    /**
     * @param int $level
     *
     * @return string|null
     */
    public static function toSentryLogLevel(int $level)
    {
        return static::LOG_LEVELS[$level] ?? null;
    }

    /**
     * Logs the message to Sentry.
     *
     * @param string|int $message
     * @param string|int $type
     * @param array      $context
     *
     * @return void
     */
    public function logInternal($message, $type, array $context = [])
    {
        $message = $this->getFormatter()->interpolate($message, $context);

        $this->send($message, $type, $context);
    }

    /**
     * Logs the exception to Sentry.
     *
     * @param \Throwable $exception
     * @param array      $context
     * @param int|null   $type
     *
     * @return void
     */
    public function logException(\Throwable $exception, array $context = [], int $type = null)
    {
        foreach ($this->config->sentry->dontReport as $ignore) {
            if ($exception instanceof $ignore) {
                return;
            }
        }

        $this->send($exception, $type, $context);
    }

    public function process(Item $item): void 
    {
        foreach ($this->config->sentry->dontReport as $ignore) {
            if ($exception instanceof $ignore) {
                return;
            }
        }
        $this->send($item->message, $item->type, $item->context);
    }

    /**
     * Sets the user context &/or identifier.
     *
     * @param array $context
     *
     * @return \CrazyFactory\PhalconLogger\Adapter\Sentry
     */
    public function setUserContext(array $context) : Sentry
    {
        if ($this->client) {
            $this->client->user_context($context);
        }

        return $this;
    }

    /**
     * Sets the extra context (arbitrary key-value pair).
     *
     * @param array $context
     *
     * @return \CrazyFactory\PhalconLogger\Adapter\Sentry
     */
    public function setExtraContext(array $context) : Sentry
    {
        if ($this->client) {
            $this->client->extra_context($context);
        }

        return $this;
    }

    /**
     * Sets the tag for logs which can be used for analysis in Sentry backend.
     *
     * @param string $key
     * @param string $value
     *
     * @return \CrazyFactory\PhalconLogger\Adapter\Sentry
     */
    public function setTag(string $key, string $value) : Sentry
    {
        if ($this->client) {
            $this->client->tags_context([$key => $value]);
        }

        return $this;
    }

    /**
     * Append bread crumbs to the Sentry log that can be used to trace process flow.
     *
     * @param string $message
     * @param string $category
     * @param array  $data
     * @param int    $type
     *
     * @return \CrazyFactory\PhalconLogger\Adapter\Sentry
     */
    public function addCrumb(string $message, string $category = 'default', array $data = [], int $type = null) : Sentry
    {
        if ($this->client) {
            $level = static::toSentryLogLevel($type ?? Logger::INFO);
            $crumb = compact('message', 'category', 'data', 'level') + ['timestamp' => time()];

            $this->client->breadcrumbs->record($crumb);
        }

        return $this;
    }

    /**
     * Gets the last event ID from Sentry.
     *
     * @return string|null
     */
    public function getLastEventId()
    {
        return $this->lastEventId;
    }

    /**
     * Sets the current request ID for sentry events.
     *
     * @param string $requestId
     *
     * @return \CrazyFactory\PhalconLogger\Adapter\Sentry
     */
    public function setRequestId(string $requestId) : Sentry
    {
        if (empty($this->requestId)) {
            $this->requestId = $requestId;
        }

        return $this;
    }

    /**
     * Sets the raven client.
     *
     * @param \Raven_Client $client
     *
     * @return \CrazyFactory\PhalconLogger\Adapter\Sentry
     */
    public function setClient(\Sentry\Client $client) : Sentry
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Gets the raven client.
     *
     * @return \Raven_Client|null
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @inheritdoc
     */
    public function getFormatter1()
    {
        if (empty($this->formatter)) {
            $this->formatter = new Formatter;
        }

        return $this->formatter;
    }

    /**
     * @inheritdoc
     */
    public function close(): bool
    {
        return true;
    }

    /**
     * Instantiates the Raven_Client.
     *
     * @return void
     */
    protected function initClient()
    {
        // Only initialize in configured environment(s).
        if (!in_array($this->config->environment, $this->config->sentry->environments->toArray(), true)) {
            return;
        }

        if (isset($this->config->sentry->dsn)) {
            $options = ['dsn' => $this->config->sentry->dsn, 'environment' => $this->config->environment] + $this->config->sentry->options->toArray();

            $this->setClient((new \Sentry\ClientBuilder( new \Sentry\Options($options)))->getClient());
        }
    }

    /**
     * Send Logs to Sentry for configured log levels.
     *
     * @param string|\Throwable $loggable
     * @param int               $type
     * @param array             $context
     *
     * @return void
     */
    protected function send($loggable, int $type, array $context = [])
    {
        if (!$this->shouldSend($type)) {
            return;
        }

        $context += ['level' => static::toSentryLogLevel($type)];

        // Wipe out extraneous keys. Issue #3.
        $context = array_intersect_key($context, array_flip([
            'context', 'extra', 'fingerprint', 'level',
            'logger', 'release', 'tags',
        ]));

        // Tag current request ID for search/trace.
        if ($this->requestId) {
            $this->client->tags_context(['request' => $this->requestId]);
        }

        // 
        $scope = null;
        if (is_array($context['extra'] ?? null)) {
            \Sentry\configureScope(function (SentryScope $mainScope) use (&$scope) {
                $scope = clone $mainScope;
            });

            foreach ($context['extra'] as $key => $value) {
                $scope->setExtra($key, $value);
            }
        }

        $this->lastEventId = ($loggable instanceof \Throwable && $loggable->getTrace() != null)
            ? $this->client->captureException($loggable, $scope)
            : $this->client->captureMessage($loggable, null, $scope);
    }

    /**
     * Should we send this log type to Sentry?
     *
     * @param int $type
     *
     * @return bool
     */
    protected function shouldSend(int $type) : bool
    {
        return (bool) $this->client && in_array($type, $this->config->sentry->levels->toArray(), true);
    }
}
