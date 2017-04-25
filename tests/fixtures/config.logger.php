<?php

use Phalcon\Logger;

return [
    'appName'     => 'phalcon-loggers',
    'environment' => 'test',
    'slack'       => [
        'webhookUrl'   => '',
        'environments' => ['prod', 'production', 'ci', 'stage', 'staging', 'test'],
        'curlMethod'   => 'sync',
        'headers'      => [],
        'levels'       => [Logger::SPECIAL],
        'context' => [
            'channel'    => '#general',
            'mrkdwn'     => true,
            'link_names' => true,
        ],
        'colors' => [
            Logger::EMERGENCE => 'danger',
            Logger::CRITICAL  => 'danger',
            Logger::ERROR     => 'danger',
            Logger::ALERT     => 'good',
            Logger::INFO      => 'good',
            Logger::NOTICE    => 'warning',
            Logger::WARNING   => 'warning',
        ],
    ],
    'sentry'      => [
        'credential' => [
            'key'       => '',
            'secret'    => '',
            'projectId' => '',
        ],
        'options' => [
            'curl_method' => 'sync',
            'prefixes'    => [],
            'app_path'    => '',
            'timeout'     => 2,
        ],
        'environments' => ['prod', 'production', 'ci', 'stage', 'staging', 'test'],
        'levels' => [Logger::EMERGENCY, Logger::CRITICAL, Logger::ERROR],
        'dontReport' => [],
    ],
];
