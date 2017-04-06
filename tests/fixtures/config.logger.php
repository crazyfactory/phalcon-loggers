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
