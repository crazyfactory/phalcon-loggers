<?php

// This example shows integration with MVC app in various ways:
// 1. Using default config and Service::register()
// 2. Using own config and no Service::register()

error_reporting(E_ALL);
ini_set('display_errors', 'On');

(new Phalcon\Loader)->registerNamespaces(['CrazyFactory\\PhalconLogger' => __DIR__ . '/../src/'])->register();

putenv('APP_NAME=phalcon-loggers');
putenv('APP_ENV=staging');
putenv('SLACK_CURL_METHOD=exec');
putenv('SENTRY_CURL_METHOD=exec');
//
// Uncomment below lines and populate the values for a test. Or you can do `export KEY=VALUE` in the CLI.
//
// putenv('SLACK_WEBHOOK_URL=');
// putenv('SENTRY_KEY=');
// putenv('SENTRY_SECRET=');
// putenv('SENTRY_PROJECT_ID=');
//
// Sentry is not enabled and loaded by default by this library, you have to run `composer require sentry/sentry`
// and register the raven autoloader like so:
require_once '/path/to/vendor/sentry/sentry/lib/Raven/Autoloader.php';
Raven_Autoloader::register();

$id = (new Phalcon\Security\Random)->uuid();
$di = new Phalcon\Di\FactoryDefault;

// 1. Using default config and Service::register().
(new CrazyFactory\PhalconLogger\Service)->setNames([
    // Aliasing the names of DI services. This is completely optional and defaults are better already.
    // It helps this library to recognize the services (like config) if they are defined with different names already.
    'logger' => 'logr',       // logger is named as logr
    'sentry' => 'bugcatcher', // sentry is named as bugcatcher
])->register(null, $di);

// Instantiate new app with DI and handle requests.
$app = new Phalcon\Mvc\Micro($di);
try {
    $app->handle();
} catch (\Throwable $e) {
    // Send the exception to all the handlers (sentry and slack currently) in logger (named as logr).
    $di->getShared('logr')->logException($e);

    // You can also directly call sentry by its name to log it.
    $di->getShared('bugcatcher')->logException($e);

    echo 'Something is wrong, your request ID is ', $id;
}

// 2. Using own config and no Service::register().
$config = [
    'environment' => 'prod',
    'requestID'   => $id,
    'sentry'      => [
        'credential' => [
            'key'       => getenv('SENTRY_KEY'),
            'secret'    => getenv('SENTRY_SECRET'),
            'projectId' => getenv('SENTRY_PROJECT_ID'),
        ],
        'options' => [
            'curl_method' => 'sync',
            'prefixes'    => [],
            'app_path'    => '',
            'timeout'     => 2,
        ],
        'environments' => ['prod', 'staging'],
        'levels' => [\Phalcon\Logger::EMERGENCY, \Phalcon\Logger::CRITICAL, \Phalcon\Logger::ERROR],
        'dontReport' => [],
    ],
    'slack' => [
        'webhookUrl'   => getenv('SLACK_WEBHOOK_URL'),
        'environments' => ['prod', 'dev'],
        'curlMethod'   => 'sync',
        'headers'      => [],
        'levels'       => [\Phalcon\Logger::SPECIAL, \Phalcon\Logger::CUSTOM],
    ],
];
$di->setShared('sentry', function () use ($config) {
    return new CrazyFactory\PhalconLogger\Adapter\Sentry($config);
});
$di->setShared('slack', function () use ($config) {
    return new CrazyFactory\PhalconLogger\Adapter\Slack($config);
});

// Instantiate new app with DI and handle requests.
$app = new Phalcon\Mvc\Micro($di);
try {
    $app->handle();
} catch (\Throwable $e) {
    // Send the exception to the sentry
    $di->getShared('sentry')->logException($e);

    // Send the exception message to the slack
    $di->getShared('slack')->special($e->getMessage());

    echo 'Something is wrong, your request ID is ', $id;
}
