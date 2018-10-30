<?php
declare(strict_types=1);

use NG\PidManager;
use Swoole\Process;

/**
 * This script starts and stops a daemon process. You can also use it to find out if the process is running or not.
 * Woo Hoo!
 * The daemon emits a string once per second.
 * STDOUT is NOT redirected by default, therefore you can pipe/redirect its output to a file
 * or another program with something like `php ./basic-daemon.php start > out.txt`
 */

/**
 * Change this to true and the output of the process will not appear on your terminal
 */
$redirectOutput = false;

require __DIR__ . '/../vendor/autoload.php';

/**
 * Help string
 */
$help = sprintf("Usage: %s start|stop|status\n", $argv[0]);

/**
 * Create a new Process ID manager so we can check up on the PID to see if it's running or not
 */
$manager = new PidManager(sprintf('%s/../var/%s.pid', __DIR__, basename(__FILE__)));

/**
 * Check whether our process is running or not
 * @param PidManager $manager
 * @return bool
 */
$isRunning = function (PidManager $manager) : bool
{
    $pids = $manager->read();
    $pid = $pids[0] ?? null;
    return ($pid && Process::kill($pid, 0));
};

/**
 * Start the process. Includes our callback that defines the process itself
 * @param PidManager $manager
 * @return bool
 */
$startDaemon = function (PidManager $manager) use ($isRunning, $redirectOutput) : bool
{
    if ($isRunning($manager)) {
        echo "Cannot start. Process already running.\n";
        return false;
    }

    $arg = null;

    $process = new Process(function(Process $child) {
        $elapsed = 0;
        while(true) {
            sleep(1);
            $elapsed++;
            printf("%d seconds have elapsed\n", $elapsed);
        }
    }, $redirectOutput);
    $process->start();
    Process::daemon();
    $manager->write($process->pid);
    printf("Process Started with pid %d\n", $process->pid);
    return true;
};

/**
 * Stop the daemon by sending a signal to the process id we've recorded in a file
 * @param PidManager $manager
 * @return bool
 */
$stopDaemon = function (PidManager $manager) use ($isRunning) : bool
{
    if (! $isRunning($manager)) {
        echo "Cannot stop process. Not currently running\n";
        return false;
    }
    $pids = $manager->read();
    // Terminate, nicely:
    $result = Process::kill($pids[0], SIGTERM);
    echo $result
        ? "Process stopped with SIGTERM\n"
        : "SIGTERM failed to stop the process\n";

    if (! $result) {
        // Kill, no really…:
        $result = Process::kill($pids[0], SIGKILL);
        echo $result
            ? "Process killed with SIGKILL\n"
            : "SIGKILL failed to stop the process\n";
    }

    if ($result) {
        $manager->delete();
    }
    return $result;
};

/**
 * A simple switch over $argv[1]
 */
$command = $argv[1] ?? null;
switch ($command) {
    case 'start':
        exit($startDaemon($manager) ? 0 : 1);
        break;
    case 'stop':
        exit($stopDaemon($manager) ? 0 : 1);
        break;
    case 'status':
        $running = $isRunning($manager);
        echo $running
            ? "Process is running\n"
            : "Process is not running\n";
        exit(0);
        break;
    default:
        echo $help;
        break;
}

