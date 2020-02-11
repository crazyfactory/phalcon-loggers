<?php

namespace CrazyFactory\PhalconLogger;

use Phalcon\Logger\Adapter\File as FileLogger;
use Phalcon\Logger\AdapterInterface;
use Phalcon\Logger\Multiple;

/**
 * Relays logs over to multiple loggers at once.
 *
 * @codeCoverageIgnore
 */
class ChainLogger extends Multiple implements AdapterInterface
{
    protected $filePath;

    /**
     * {@inheritdoc}
     */
    public function getFormatter()
    {
        // Not used, required as a part of interface implementation!
    }

    /**
     * {@inheritdoc}
     */
    public function begin()
    {
        // Not used, required as a part of interface implementation!
    }

    /**
     * {@inheritdoc}
     */
    public function commit()
    {
        // Not used, required as a part of interface implementation!
    }

    /**
     * {@inheritdoc}
     */
    public function rollback()
    {
        // Not used, required as a part of interface implementation!
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        // Not used, required as a part of interface implementation!
    }

    /**
     * Attach file log path.
     *
     * @param string $filePath full file path
     *
     * @return self
     */
    public function withFile(string $filePath): self
    {
        $this->filePath = $filePath;

        $fileLogger = new FileLogger($filePath);

        $fileLogger->setFormatter(new LineFormatter('[%date%][%type%] %message%%extra%', 'Y-m-d H:i:s'));

        $this->push($fileLogger);

        return $this;
    }

    public function getFilePath()
    {
        return $this->filePath;
    }
}
