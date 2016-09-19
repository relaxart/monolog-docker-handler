<?php

namespace DockerMonologHandler;

use Monolog\Formatter\LineFormatter;
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
    protected $process;

    /**
     * DockerHandler constructor.
     * @param int      $process
     * @param bool|int $level
     * @param bool     $bubble
     */
    public function __construct($process = 1, $stream = 2, $level = Logger::DEBUG, $bubble = true)
    {
        $this->process = "/proc/{$process}/fd/{$stream}";
        parent::__construct($level, $bubble);
    }

    /**
     * {@inheritdoc}
     */
    protected function write(array $record)
    {
        $rs = popen("cat - > ".$this->process, 'w');
        fwrite($rs, $record["formatted"]);
        pclose($rs);
    }

    /**
     * {@inheritDoc}
     */
    protected function getDefaultFormatter()
    {
        return new LineFormatter();
    }
}

