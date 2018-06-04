<?php

namespace Easyconn\PhalconLogger;

use Phalcon\Logger;

class Formatter extends Logger\Formatter
{
    /**
     * @inheritdoc
     */
    public function format($message, $type, $timestamp, $context = null)
    {
        return $this->interpolate($message, $context ?: []);
    }
}
