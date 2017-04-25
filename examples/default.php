<?php

// This example shows integration with default config using the shipped Service::register();

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
// require_once '/path/to/vendor/sentry/sentry/lib/Raven/Autoloader.php';
// Raven_Autoloader::register();

$id = (new Phalcon\Security\Random)->uuid();
$di = new Phalcon\Di\FactoryDefault;

// Service for loggers.
$service = new CrazyFactory\PhalconLogger\Service;

// If you want to disable lets say slack then you could pass in false as service name like so:
// $service->setNames(['slack' => false]);

// Register loggers using default config.
$service->register(null, $di);

// Or you could just do it in a single line:
// (new CrazyFactory\PhalconLogger\Service)->setNames([...])->register(null, $di);

$logger = $di->getShared('logger');

// Set the request ID for current session, which is used by each of the registered loggers (only sentry has this feature ATM).
$logger->setRequestId($id);

$logger->special('slack:: test package phalcon-loggers ' . $id, [
    'mentions' => 'channel',
    'attachment' => [
        'title' => 'Attachment title',
        'text' => 'Attachment text',
        'color' => 'good',
    ],
    'username' => 'adhocore',
    'channel' => '#general',
    'icon_emoji' => ':+1:',
]);
// $logger->critical('sentry:: test package phalcon-loggers ' . $id);

// Give back the feedback to user.
echo 'Something is wrong, your request ID is ', $id;

// In the sentry backend, you can search/trace this event by searching it like so: 'request:{$id}'
