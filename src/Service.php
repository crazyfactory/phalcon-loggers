<?php

namespace CrazyFactory\PhalconLogger;

use Phalcon\Config;
use Phalcon\Di;
use Phalcon\Logger\AdapterInterface;

class Service
{
    // The service key => name pair in DI. Use (Service::setNames() to override).
    protected $services = [
        'logger' => 'logger',
        'config' => 'config',
        'sentry' => false,
        'slack'  => 'slack',
    ];

    // Loggers provided by the service and their FQCN.
    private $loggers = [
        'slack'  => __NAMESPACE__ . '\\Adapter\\Slack',
        'sentry' => __NAMESPACE__ . '\\Adapter\\Sentry',
    ];

    /**
     * Constructor.
     */
    public function __construct()
    {
        if (class_exists('\Raven_Client')) {
            $this->services['sentry'] = 'sentry';
        }
    }

    /**
     * Register the loggers into DI individually and as a multiple handler
     *
     * @param \Phalcon\Config|array|string $config
     * @param \Phalcon\Di|null             $di
     *
     * @return void
     */
    public function register($config = null, Di $di = null)
    {
        $di = $di ?: Di::getDefault();

        if (empty($config) && $di->has($this->services['config'])) {
            $config = $di->getShared($this->services['config']);
        }

        if (empty($config)) {
            $config = __DIR__ . '/config.logger.php';
        }

        if (is_string($config) && is_file($config)) {
            $config = require_once $config;
        }

        if (is_array($config)) {
            $config = new Config($config);
        }

        if (!$config instanceof Config || !$config->environment) {
            throw new \InvalidArgumentException('Invalid configuration parameter');
        }

        // Enabled loggers.
        $loggers = [];

        // Register enabled loggers.
        foreach ($this->loggers as $service => $class) {
            // Skip loggers that are disabled.
            if (false === $name = $this->services[$service]) {
                continue;
            }

            $loggers[] = $service;
            $di->setShared($service, function () use ($config, $class) {
                return new $class($config);
            });
        }

        // The logger already in DI.
        $logger = $di->has($this->services['logger']) ? $di->getShared($this->services['logger']) : null;

        $di->setShared($this->services['logger'], function () use ($config, $di, $loggers, $logger) {
            $loggerStack = new Multiple;

            // Keep the logger that is already there in DI.
            if ($logger instanceof AdapterInterface) {
                $loggerStack->push($logger);
            }

            foreach ($loggers as $service) {
                $loggerStack->push($di->getShared($service));
            }

            if ($config->requestId) {
                $loggerStack->setRequestId($config->requestId);
            }

            return $loggerStack;
        });
    }

    /**
     * Sets the DI service names.
     *
     * Set the value to false to disable a logger, or string to use custom name.
     * Example: (new Service)->setNames(['sentry' => false, 'config' => 'conf'])->register();
     *
     * @param array $services
     *
     * @return \CrazyFactory\PhalconLogger\Service
     */
    public function setNames(array $services) : Service
    {
        $this->services = $services + $this->services;

        return $this;
    }
}
