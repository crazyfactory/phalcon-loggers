<?php

namespace CrazyFactory\PhalconLogger\Adapter;

use CrazyFactory\PhalconLogger\LineFormatter;
use Phalcon\Config;
use Phalcon\Logger\Adapter as LoggerAdapter;

abstract class BaseLogger extends LoggerAdapter
{
    protected $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     */
    public function close()
    {
        // Not used, required as a part of interface implementation!
    }
}
