## phalcon-loggers ![build status](https://api.travis-ci.org/crazyfactory/phalcon-loggers.svg?branch=master)

A collection of configurable logging adapters with logging functionality loosely PSR compatible for phalcon 3.x and PHP 7.x.
Currently the following adapters are implemented:

#### Sentry
- Depends on the [sentry/sentry](https://packagist.org/packages/sentry/sentry) package.
- You have to run `composer require sentry/sentry`
- You might have to require `Raven_Autoloader` as explained [here](https://docs.sentry.io/clients/php/#installation) (see #installation section below)
- Disabled by default but is active if class `Raven_Client` is defined.

#### Slack
- Self contained and ready to work out of the box.

*... and we plan to add a few more!*

## Installation
It is a fully composer ready library. Just run:
```
$ composer require crazyfactory/phalcon-loggers
```

If you have not used composer's autoloading (i.e. `require 'vendor/autoload.php'`) &/or just want to use phalcon's native autoloader:
```php
$loader = new Phalcon\Loader;
$loader->registerNamespaces([
    'CrazyFactory\\PhalconLogger' => '/path/to/vendor/crazyfactory/phalcon-loggers/src/',
])->register();

```

If you want to use Sentry as well:
```
$ composer require sentry/sentry
```

If you have not used composer's autoloading, then setup autoloading of `Raven_` classes like so:
```php
require_once '/path/to/vendor/sentry/sentry/lib/Raven/Autoloader.php';
Raven_Autoloader::register();
```

## Usage

The easiest and quickest way to use this library is by using the supplied `Service`:

```php
$id = (new Phalcon\Security\Random)->uuid();
$di = new Phalcon\Di\FactoryDefault;
(new CrazyFactory\PhalconLogger\Service)->register(null, $di);

// If you already have `requestId` in config array/object you don't need to set it again.
$di->getShared('logger')->setRequestId($id);

$di->getShared('logger')->info('some text');
// Supports interpolation for keys wrapped in curly brace.
$di->getShared('logger')->critical('some text {key}', ['key' => 'val']);

//
// Below examples assume that info level is allowed in config->slack->levels array.
//
// Mention an user in slack:
$context = ['mentions' => 'slackbot', 'a' => 10];
$di->getShared('slack')->info('some text {a}', $context);

// Customize channel, username, icon_emoji, icon_url via context:
$context += [
    'username' => 'bot',
    'channel' => '#general',
    'icon_emoji' => ':monkey_face:',
];
$di->getShared('slack')->info('some other text {a}', $context);

// Attachment:
$context += [
    'attachment' => [
        'title' => 'Attachment title',
        'text' => 'Attachment text',
        'color' => 'good',
    ],
];
$di->getShared('slack')->info('yet other text {a} with attachment', $context);
```

See [examples](examples/) for details and various integration samples.
Also see the [API](#api) section.

## Configuration

You can pass in configuration as an array or a file
The loggers have their own configuration options but share common parameter `requestId` and `environment`.
Here is how a typical configuration looks like for slack and sentry both:

```php
use Phalcon\Logger;

$config = [
    // The application name used in logs. Helps to distinguish &/or search.
    'appName' => '',
    // Current application environment.
    'environment' => 'prod',
    'requestID'   => null,
    'sentry'      => [
        // The login information for Sentry. If one of the values is empty the logging is suppressed silently.
        'credential' => [
            'key'       => '',
            'secret'    => '',
            'projectId' => '',
        ],
        // The options for Raven_Client. See https://docs.sentry.io/clients/php/config/#available-settings
        'options' => [
            'curl_method' => 'sync',
            'prefixes'    => [],
            'app_path'    => '',
            'timeout'     => 2,
        ],
        // Sentry will log errors/exceptions when the application environment set above is one of these.
        'environments' => ['prod', 'staging'],
        // The log levels which are forwarded to sentry.
        'levels' => [Logger::EMERGENCY, Logger::CRITICAL, Logger::ERROR],
        // These exceptions are not reported to sentry.
        'dontReport' => [],
    ],
    'slack' => [
        // If webhook url is missing the logging is suppressed silently.
        'webhookUrl'   => '',
        // Slack will log messages when the application environment set above is one of these.
        'environments' => ['prod', 'dev'],
        // Curl method can be 'sync' or 'exec' (sync uses php curl_*, exec runs in background).
        'curlMethod'   => 'sync',
        // HTTP headers to be appended to request.
        'headers'      => [],
        // The log levels which are forwarded to slack.
        'levels'       => [Logger::SPECIAL, Logger::CUSTOM],
    ],
];

$di = new Phalcon\Di\FactoryDefault;

// Register the loggers with the config:
$di->setShared('sentry', function () use ($config) {
    return new CrazyFactory\PhalconLogger\Adapter\Sentry($config);
});
$di->setShared('slack', function () use ($config) {
    return new CrazyFactory\PhalconLogger\Adapter\Slack($config);
});

// OR you could just use supplied `Service`:
(new CrazyFactory\PhalconLogger\Service)->register($config, $di);

```


## <a name="api"></a>API
All the loggers inherit from base phalcon logger [adapter](https://github.com/phalcon/cphalcon/blob/master/phalcon/logger/adapter.zep) so they automatically inherit public callable APIs from the base.
In addition the loggers may also expose some APIs available for direct call.

#### Sentry:
Sentry logger has following public APIs:

- **addCrumb(string $message, string $category = 'default', array $data = [], int $type = null)**
```php
    $di->getShared('sentry')->addCrumb('User has just logged in');
```

- **setTag(string $key, string $value)**
```php
    $di->getShared('sentry')->setTag('name', 'value')->setTag('another', 'another-value');
```

- **setUserContext(array $context)**
```php
    // you can also use current user from DI if you have one
    $di->getShared('sentry')->setUserContext(['id' => 1, 'username' => 'someone', 'email' => 'bob@example.com']);
```

- **setExtraContext(array $context)**
```php
    $di->getShared('sentry')->setExtraContext(['arbitrary_key' => 'arbitrary_value', 'arbitrary_key_2', 'arbitrary_value_2']);
```

- **getLastEventId()**
```php
    $di->getShared('sentry')->getLastEventId();
```

- **logException(\Throwable $exception, array $context = [], int $type = null)**
```php
    try {
        $app->handle();
    } catch (\Throwable $e) {
        $di->getShared('sentry')->logException($e);
        // However it is advisable to just use logException of the logger collection
        // so that all the registered loggers are notified of the exception to do the needful.
        $di->getShared('logger')->logException($e);
    }
```
