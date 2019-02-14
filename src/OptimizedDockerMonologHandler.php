<?php

declare(strict_types=1);

namespace DockerMonologHandler;

use Monolog\Formatter\FormatterInterface;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;

/**
 * Log messages to container output
 * Due to some limitations of php-fpm, we need to write logs directly to container stdout/stderr
 *
 * @codeCoverageIgnore
 */
class OptimizedDockerMonologHandler extends AbstractProcessingHandler
{

    /**
     * @var resource
     */
    private $resource;

    /**
     * @var string
     */
    private $command;

    /**
     * Constructor
     *
     * @param int $processId PID, this should be 1
     * @param int $fileDescriptor Accept 1: stdout, or 2: stderr
     * @param string|int $level Log level
     * @param FormatterInterface|null $formatter
     * @param bool $bubble
     */
    public function __construct(
        int $processId = 1,
        int $fileDescriptor = 2,
        $level = Logger::DEBUG,
        FormatterInterface $formatter = null,
        bool $bubble = true
    )
    {
        $this->command = sprintf('cat - >> /proc/%d/fd/%d', $processId, $fileDescriptor);

        parent::__construct($level, $bubble);

        if (null !== $formatter) {
            $this->setFormatter($formatter);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        if (is_resource($this->resource)) {
            pclose($this->resource);
        }

        parent::close();
    }

    /**
     * {@inheritdoc}
     */
    protected function write(array $record)
    {
        if (!is_resource($this->resource)) {
            $this->resource = popen($this->command, 'w');
        }

        fwrite($this->resource, (string)$record['formatted']);
    }
}

