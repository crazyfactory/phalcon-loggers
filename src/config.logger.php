<?php

// This is an example configuration for loggers. It is used as fallback by Service::register().
// You can copy it in your project as src/config/logger.php OR even embed it to global application configuration.

return [
    // The application name used in logs. Helps to distinguish &/or search.
    'appName' => getenv('APP_NAME'),

    'requestId' => null, // str_replace('-', '', (new \Phalcon\Security\Random)->uuid())

    // Current application environment. Defaults to 'dev' which is not logged by default.
    'environment' => getenv('APP_ENV') ?: 'dev',

    // Sentry configuration.
    'sentry' => [

        // The login information for Sentry.
        'credential' => [
            'key'       => getenv('SENTRY_KEY'),
            'secret'    => getenv('SENTRY_SECRET'),
            'projectId' => getenv('SENTRY_PROJECT_ID'),
        ],

        // The options for Raven_Client. See https://docs.sentry.io/clients/php/config/#available-settings
        'options' => [
            'curl_method' => getenv('SENTRY_CURL_METHOD') ?: 'sync',
            'prefixes'    => [],
            'app_path'    => '',
            'timeout'     => 2,
        ],

        // Sentry will log errors/exceptions when the application environment set above is one of these.
        'environments' => ['prod', 'production', 'ci', 'stage', 'staging'],

        // The log levels which are forwarded to sentry.
        'levels' => [
            \Phalcon\Logger\Logger::EMERGENCY,
            \Phalcon\Logger\Logger::CRITICAL,
            \Phalcon\Logger\Logger::ERROR,
        ],

        // These exceptions are not reported to sentry.
        'dontReport' => [
            \Phalcon\Http\Request\Exception::class,
            \Phalcon\Http\Response\Exception::class,
            \Phalcon\Validation\Exception::class,
        ],
    ],

    // Slack configuration.
    'slack' => [

        // Looks something like https://hooks.slack.com/services/xxxxxxxxx/xxxxxxxxxx/xxxxxxxxxxxxxxxxxxx
        'webhookUrl' => getenv('SLACK_WEBHOOK_URL'),

        // Slack will log messages when the application environment set above is one of these.
        'environments' => ['prod', 'production', 'ci', 'stage', 'staging'],

        // Curl method can be 'sync' or 'exec' (sync uses php curl_*, exec runs in background).
        'curlMethod' => getenv('SLACK_CURL_METHOD') ?: 'sync',

        // HTTP headers to be appended to request.
        'headers'  => [],

        // The log levels which are forwarded to slack.
        'levels' => [
            \Phalcon\Logger\Logger::SPECIAL,
            \Phalcon\Logger\Logger::CUSTOM,
        ],

        // The default context. Can be overridden with context parameter on each call to log:
        // Eg: di->get('slack')->log(level, msg, overrideContext).
        // Also supported: 'icon_emoji', 'icon_url' and 'username' contexts here.
        'context' => [
            'channel'    => '#general',
            'mrkdwn'     => true,
            'link_names' => true,
        ],

        // Color map for attachments.
        'colors' => [
            \Phalcon\Logger\Logger::EMERGENCE => 'danger',
            \Phalcon\Logger\Logger::CRITICAL  => 'danger',
            \Phalcon\Logger\Logger::ERROR     => 'danger',
            \Phalcon\Logger\Logger::ALERT     => 'good',
            \Phalcon\Logger\Logger::INFO      => 'good',
            \Phalcon\Logger\Logger::NOTICE    => 'warning',
            \Phalcon\Logger\Logger::WARNING   => 'warning',
        ],
    ],
];
