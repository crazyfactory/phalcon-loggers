<?php

namespace CrazyFactory\PhalconLogger;

use Phalcon\Logger\Formatter\Line;

class LineFormatter extends Line
{
    public function interpolate($message, $context = null)
    {
        if (empty($context)) {
            return str_replace('%extra%', '', $message);
        }

        $context = (array) $context;
        $message = str_replace('%extra%', !empty($context['extra']) ? "\n  " . json_encode($context['extra']) : '', $message);

        foreach ($context as $key => &$value) {
            if (!\is_scalar($value)) {
                $value = is_array($value) ? json_encode($value) : gettype($value);
            }
        }

        return parent::interpolate($message, $context);
    }
}
