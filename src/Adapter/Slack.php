<?php

namespace CrazyFactory\PhalconLogger\Adapter;

use CrazyFactory\PhalconLogger\Formatter;
use Phalcon\Config;
use Phalcon\Logger\Adapter;

/**
 * The Slack logger adapter for phalcon.
 */
class Slack extends Adapter
{
    /** @var \Phalcon\Config */
    protected $config;

    /** @var int The number of logs/messages sent to slack so far in current session */
    protected $eventCount = 0;

    /**
     * Instantiates new Slack Adapter with given configuration.
     *
     * @param \Phalcon\Config|array $config
     */
    public function __construct($config)
    {
        if (is_array($config)) {
            $config = new Config($config);
        }

        if (!$config instanceof Config) {
            throw new \InvalidArgumentException('Configuration parameter must be an array or instance of ' . Config::class);
        }

        $this->config = $config;
    }

    /**
     * Logs the message to Slack channel.
     *
     * @param string $message
     * @param int    $type
     * @param int    $time
     * @param array  $context
     *
     * @return void
     */
    public function logInternal($message, $type, $time, array $context = [])
    {
        $context += compact('time', 'type');

        // Send if web hook is configured and the desired log level is met.
        if ($this->shouldSend($type)) {
            $this->eventCount++;
            $this->config->slack->curlMethod === 'exec'
                ? $this->sendExec($this->preparePayload($message, $context))
                : $this->send($this->preparePayload($message, $context));
        }
    }

    /**
     * Prepare JSON payload from message and context.
     *
     * @param string $message
     * @param array  $context
     *
     * @return string
     */
    public function preparePayload(string $message, array $context = []) : string
    {
        $mentions = '';
        $message  = $this->getFormatter()->interpolate($message, $context);

        $context['mentions'] = $context['mentions'] ?? [];
        if (is_string($context['mentions'])) {
            $context['mentions'] = explode(',', $context['mentions']);
        }

        foreach ($context['mentions'] as $user) {
            $mentions .= ' <@' . trim($user, '@ ') . '>';
        }

        // APP name distinguishes the logs if the slack channel is shared. It also eases searching in slack.
        if ($appName = $this->config->appName) {
            $appName = "*{$appName}@{$this->config->environment}*";
        }

        $payload = isset($this->config->slack->context)
            ? $this->config->slack->context->toArray()
            : [];

        $payload += ['text' => "{$appName}{$mentions}\n{$message}"];

        // Attachment.
        if (!empty($context['attachment']) && is_array($context['attachment'])) {
            $colors = isset($this->config->slack->colors)
                ? $this->config->slack->colors->toArray()
                : [];

            $payload['attachments'] = [$context['attachment'] + [
                'color'     => $colors[$context['type'] ?? -1] ?? '#e3e4e6',
                'fallback'  => $message,
                'mrkdwn_in' => ['fields'],
                'text'      => $message,
                'ts'        => $context['time'] ?? time(),
            ]];
        }

        foreach (['channel', 'icon_url', 'icon_emoji', 'username'] as $key) {
            if (!empty($context[$key])) {
                $payload[$key] = $context[$key];
            }
        }

        return json_encode($payload, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Get the number of logs/messages sent to slack so far in current session.
     *
     * @return int
     */
    public function getEventCount() : int
    {
        return $this->eventCount;
    }

    /**
     * @inheritdoc
     */
    public function getFormatter()
    {
        if (empty($this->_formatter)) {
            $this->_formatter = new Formatter;
        }

        return $this->_formatter;
    }

    /**
     * @inheritdoc
     */
    public function close()
    {
    }

    /**
     * Send log using system curl request to Slack.
     *
     * @param  string $payload
     *
     * @return bool
     */
    protected function sendExec(string $payload) : bool
    {
        $command = 'curl -X POST ';
        $headers = ['Content-Type' => 'application/json'] + $this->config->slack->headers->toArray();

        foreach ($headers as $key => $value) {
            $command .= '-H ' . escapeshellarg("$key: $value") . ' ';
        }

        // Fire and forget.
        exec("$command --data '{$payload}' {$this->config->slack->webhookUrl} > /dev/null 2>&1 &");

        return true;
    }

    /**
     * Send log using php curl request to Slack.
     *
     * @param  string $payload
     *
     * @return bool
     */
    protected function send(string $payload) : bool
    {
        $curl    = curl_init($this->config->slack->webhookUrl);
        $headers = ['Content-Type' => 'application/json'] + $this->config->slack->headers->toArray();

        curl_setopt_array($curl, [
            CURLOPT_POST           => true,
            CURLOPT_VERBOSE        => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_POSTFIELDS     => $payload,
        ]);

        curl_exec($curl);

        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        return $code == 200;
    }

    /**
     * Should we send this log type to Slack?
     *
     * @param  int $type
     *
     * @return bool
     */
    protected function shouldSend(int $type) : bool
    {
        return !empty($this->config->slack->webhookUrl)
            && in_array($this->config->environment, $this->config->slack->environments->toArray(), true)
            && in_array($type, $this->config->slack->levels->toArray(), true);
    }
}
