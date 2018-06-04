<?php

namespace Easyconn\PhalconLogger;

use Phalcon\Logger;

class Multiple extends Logger\Multiple
{
    /**
     * @param \Throwable $exception
     * @param array      $context
     * @param int|null   $type
     *
     * @return void
     */
    public function logException(\Throwable $exception, array $context = [], int $type = null)
    {
        $type = $type ?? Logger::ERROR;

        foreach ($this->_loggers as $logger) {
            if (method_exists($logger, 'logException')) {
                $logger->logException($exception, $context, $type);

                continue;
            }

            $context += [
                'class'   => get_class($exception),
                'file'    => $exception->getFile(),
                'line'    => $exception->getLine(),
                'code'    => $exception->getCode(),
                'message' => $exception->getMessage(),
            ];

            $logger->log($type, "{class}#{code} with message `{message}` thrown at {file}:{line}", $context);
        }
    }

    /**
     * Sends/Writes special message to the log.
     *
     * @param  string $message
     * @param  array  $context
     *
     * @return void
     */
    public function special(string $message, array $context = [])
    {
        $this->log(Logger::SPECIAL, $message, $context);
    }

    /**
     * Sends/Writes custom message to the log.
     *
     * @param  string $message
     * @param  array  $context
     *
     * @return void
     */
    public function custom(string $message, array $context = [])
    {
        $this->log(Logger::CUSTOM, $message, $context);
    }

    /**
     * Sets the current request ID for logs.
     *
     * Useful for searching &/or tracing, should be dynamic 32 char uuid.
     *
     * @param string $requestId
     *
     * @return \CrazyFactory\PhalconLogger\Multiple
     */
    public function setRequestId(string $requestId) : Multiple
    {
        $requestId = substr(str_replace([' ', '-', '_'], '', $requestId), 0, 32);

        foreach ($this->_loggers as $logger) {
            if (method_exists($logger, 'setRequestId')) {
                $logger->setRequestId($requestId);
            }
        }

        return $this;
    }
}
