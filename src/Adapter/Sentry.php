<?php

namespace CrazyFactory\PhalconLogger\Adapter;

use Phalcon\Config;
use Phalcon\Logger;

/**
 * The Sentry logger adapter for phalcon.
 */
class Sentry extends Logger\Adapter
{
    // The map of Phalcon log levels to Sentry log levels. Throughout the application, we use only Phalcon levels.
    const LOG_LEVELS = [
        Logger::EMERGENCE => \Raven_Client::FATAL,
        Logger::CRITICAL  => \Raven_Client::FATAL,
        Logger::ALERT     => \Raven_Client::INFO,
        Logger::ERROR     => \Raven_Client::ERROR,
        Logger::WARNING   => \Raven_Client::WARNING,
        Logger::NOTICE    => \Raven_Client::DEBUG,
        Logger::INFO      => \Raven_Client::INFO,
        Logger::DEBUG     => \Raven_Client::DEBUG,
        Logger::CUSTOM    => \Raven_Client::INFO,
        Logger::SPECIAL   => \Raven_Client::INFO,
    ];

    /** @var \Raven_Client */
    protected $client;

    /** @var string The sentry event ID from last request */
    protected $lastEventId;

    /** @var string The request ID for tagging sentry events */
    protected $requestId;

    /** @var Config */
    protected $config;

    protected $dsnTemplate = 'https://<key>:<secret>@sentry.io/<project>';

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
        if (is_numeric($level)) {
            return $level;
        }

        return array_flip(static::LOG_LEVELS)[$level] ?? null;
    }

    /**
     * @param int $level
     *
     * @return string|null
     */
    public static function toSentryLogLevel(int $level)
    {
        if (!is_numeric($level)) {
            return $level;
        }

        return static::LOG_LEVELS[$level] ?? null;
    }

    /**
     * Logs the message to Sentry.
     *
     * @param string|int $message
     * @param string|int $type
     * @param int        $time
     * @param array      $context
     *
     * @return void
     */
    public function logInternal($message, $type, int $time, array $context = [])
    {
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
     * @inheritdoc
     */
    public function getFormatter()
    {
        return $this->_formatter;
    }

    /**
     * @inheritdoc
     */
    public function close()
    {
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

        $key     = $this->config->sentry->credential->key;
        $secret  = $this->config->sentry->credential->secret;
        $project = $this->config->sentry->credential->projectId;

        if ($key && $secret && $project) {
            $dsn     = str_replace(['<key>', '<secret>', '<project>'], [$key, $secret, $project], $this->dsnTemplate);
            $options = ['environment' => $this->config->environment] + $this->config->sentry->options->toArray();

            $this->client = new \Raven_Client($dsn, $options);
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

        // Tag current request ID for search/trace.
        if ($this->requestId) {
            $this->client->tags_context(['request' => $this->requestId]);
        }

        $this->lastEventId = $loggable instanceof \Throwable
            ? $this->client->captureException($loggable, $context)
            : $this->client->captureMessage($loggable, [], $context);
    }

    /**
     * Should we send this log type to Slack?
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
