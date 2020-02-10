<?php

namespace CrazyFactory\PhalconLogger;

use CrazyFactory\PhalconLogger\LineFormatter;
use Phalcon\Config;
use Phalcon\Logger;
use Sentry\Severity;
use Sentry\State\HubInterface;
use Sentry\State\Scope as SentryScope;

/**
 * Logs to the sentry.
 */
class SentryLogger extends BaseLogger
{
    const SENTRY_LEVELS = [
        Logger::EMERGENCE => Severity::FATAL,
        Logger::CRITICAL => Severity::FATAL,
        Logger::ALERT => Severity::INFO,
        Logger::ERROR => Severity::ERROR,
        Logger::WARNING => Severity::WARNING,
        Logger::NOTICE => Severity::DEBUG,
        Logger::INFO => Severity::INFO,
        Logger::DEBUG => Severity::DEBUG,
        Logger::CUSTOM => Severity::INFO,
        Logger::SPECIAL => Severity::INFO,
    ];

    /** @var HubInterface */
    private $sentry;

    public function __construct(Config $config, HubInterface $sentry)
    {
        $this->sentry = $sentry;
        parent::__construct($config);
    }

    /**
     * Send to sentry if configured.
     */
    protected function logInternal(string $message, int $type, int $time, array $context)
    {
        $di = \Phalcon\Di::getDefault();


        // format() is required as it does interpolation and other processing required!
        $message = $this->getFormatter()->format($message, $type, $time, $context);

        $scope = null;
        if (is_array($context['extra'] ?? null)) {
            \Sentry\configureScope(function (SentryScope $mainScope) use (&$scope) {
                $scope = clone $mainScope;
            });

            foreach ($context['extra'] as $key => $value) {
                $scope->setExtra($key, $value);
            }
        }

        if ($this->config->sentry->level >= $type) {
            $level = self::SENTRY_LEVELS[$type] ?? Severity::INFO;
            $this->sentry->getClient()->captureMessage($message, Severity::$level(), $scope);
        }
    }

    /**
     * Gets a plain line formatter with message format `[%type%] %message%`.
     *
     * @return LineFormatter
     */
    public function getFormatter(): LineFormatter
    {
        if (!$this->_formatter) {
            // The datetime is omitted as the remote services set them when something is logged.
            $this->_formatter = new LineFormatter('[%type%] %message%');
        }

        return $this->_formatter;
    }

}
