<?php
declare(strict_types=1);
/**
 * Shamelessly copied from ZE Swoole Runtime but adapted to cope with however many pids we might need?
 * @link https://github.com/zendframework/zend-expressive-swoole/blob/master/src/PidManager.php
 */

namespace NG;

use InvalidArgumentException;
use RuntimeException;
use function array_filter;
use function array_walk;
use function dirname;
use function explode;
use function file_get_contents;
use function file_put_contents;
use function implode;
use function is_numeric;
use function is_readable;
use function is_writable;
use function sprintf;
use function unlink;

class PidManager
{
    /** @var string */
    private $pidFile;

    public function __construct(string $pidFile)
    {
        $this->pidFile = $pidFile;
        if (! is_writable($this->pidFile) && ! is_writable(dirname($this->pidFile))) {
            throw new RuntimeException(sprintf('Pid file "%s" is not writable', $this->pidFile));
        }
    }

    /**
     * Write all given pids to the pid file
     *
     * @param array $pids
     */
    public function write(...$pids) : void
    {
        $notNumber = array_filter($pids, function ($pid) {
            return ! is_numeric($pid);
        });
        if (! empty($notNumber)) {
            throw new InvalidArgumentException('Only numbers can be saved to the pid file');
        }
        file_put_contents($this->pidFile, implode(',', $pids));
    }

    /**
     * Read pids from pid file
     *
     * @return int[]
     */
    public function read() : array
    {
        $pids = [];
        if (is_readable($this->pidFile)) {
            $content = file_get_contents($this->pidFile);
            $pids = explode(',', $content);
        }
        array_walk($pids, function (&$pid) {
            $pid = (int) $pid;
        });
        return $pids;
    }

    /**
     * Delete pid file
     */
    public function delete() : bool
    {
        if (is_writable($this->pidFile)) {
            return unlink($this->pidFile);
        }
        return false;
    }
}
