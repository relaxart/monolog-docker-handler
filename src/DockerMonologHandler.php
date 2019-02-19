<?php

namespace DockerMonologHandler;

use Monolog\Formatter\LineFormatter;
use Monolog\Formatter\FormatterInterface;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;

/**
 * Logs to a Docker container output.
 *
 * usage example:
 *
 *   $log = new Logger('application');
 *   $docker = new DockerHandler();
 *   $log->pushHandler($docker);
 *
 * @author Ilya Isaev <me@ilyaisaev.com>
 */
class DockerMonologHandler extends AbstractProcessingHandler
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
     * @param bool $bubble
     * @param FormatterInterface|null $formatter
     */
    public function __construct(
        int $processId = 1,
        int $fileDescriptor = 2,
        $level = Logger::DEBUG,
        bool $bubble = true,
        FormatterInterface $formatter = null
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

    /**
     * {@inheritDoc}
     */
    protected function getDefaultFormatter()
    {
        return new LineFormatter();
    }
}

