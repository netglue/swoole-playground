<?php
declare(strict_types=1);

namespace NG;

use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server as HttpServer;
use Swoole\Process;
use function printf;
use function sprintf;
use function usleep;
use const SWOOLE_PROCESS;

/**
 * Return a string that reports on the state of the server's master and manager pids
 * @param HttpServer $server
 * @return string
 */
$reportServerProcessState = function (HttpServer $server) : string {
    $master = Process::kill($server->master_pid, 0);
    $manager = Process::kill($server->manager_pid, 0);
    return sprintf(
        'Master is %s and Manager is %s',
        $master ? 'Still Running' : 'Not Running',
        $manager ? 'Still Running' : 'Not Running'
    );
};

/**
 * Create server instance
 */
$httpServer = new HttpServer('127.0.0.1', 9001, SWOOLE_PROCESS);
$httpServer->set([
    'worker_num' => 2,
    'daemonize' => false, // Don't daemonize (The default)
]);

/**
 * The onRequest event is required to get a server running
 */
$httpServer->on('Request', function (Request $req, Response $resp) {
});

/**
 * This callback is fired when the server starts
 */
$httpServer->on('Start', function (HttpServer $server) use ($reportServerProcessState) {
    printf(
        "In the start event, before immediate termination %s\n",
        $reportServerProcessState($server)
    );
    // Give the server a chance to start up
    usleep(10000);
    // Stops Worker Processes
    $server->stop();
    // Shuts down the server
    $server->shutdown();
    printf(
        "In the start event, AFTER termination %s\n",
        $reportServerProcessState($server)
    );
});
$httpServer->on('Shutdown', function (HttpServer $server) use ($reportServerProcessState) {
    printf(
        "In the shutdown event, %s\n",
        $reportServerProcessState($server)
    );
});

/**
 * Starting the server will block here unless the daemonize parameter is set
 */
$httpServer->start();

printf(
    'After $server->start() has released control back to the main process: %s' . PHP_EOL,
    $reportServerProcessState($httpServer)
);

exit(0);
