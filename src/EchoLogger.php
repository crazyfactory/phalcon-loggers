<?php

namespace CrazyFactory\PhalconLogger;

use CrazyFactory\PhalconLogger\LineFormatter;
use Phalcon\Config;

/**
 * Echo logger echoes the log data right away. Designed to use in CLI tasks.
 */
class EchoLogger extends BaseLogger
{
    protected $format;

    public function __construct(Config $config, string $format = null)
    {
        $this->format = $format ?: '[%type%][%date%] %message%';
        parent::__construct($config);
    }

    /**
     * Echoes the log message as it is in CLI!
     */
    protected function logInternal(string $message, int $type, int $time, array $context)
    {
        if (PHP_SAPI !== 'cli') {
            return;
        }

        if ($this->config->echoLogger->level >= $type) {
            if ($context['asSummary'] ?? false) {
                echo $this->getFormatter()->interpolate($message, $context);

                return;
            }
            echo $this->getFormatter()->format($message, $type, $time, $context);
        }
    }

    /**
     * Gets a plain line formatter with configured format.
     *
     * @return LineFormatter
     */
    public function getFormatter(): LineFormatter
    {
        if (!$this->_formatter) {
            $this->_formatter = new LineFormatter($this->format, 'Y-m-d H:i:s');
        }

        return $this->_formatter;
    }

}
