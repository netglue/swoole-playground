<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;
use function NG\getCliPortNumberWithDefault;
use function NG\setProcessName;

/**
 * This is overridden in on('start') because it's the same process scope.
 * If, we were in SWOOLE_PROCESS mode, probably, we'd be able to set the name of the
 * manager process in the on('managerstart') event
 */
setProcessName('http-server-parent-process');

$server = new Server('127.0.0.1', getCliPortNumberWithDefault(), SWOOLE_BASE);
$server->set([
    'worker_num' => 4,
    'task_worker_num' => 4,
]);
$server->on('start', function (Server $server) {
    setProcessName('http-server-master-process');
});
$server->on('request', function (Request $request, Response $response) {
    $response->status(200);
    $response->header('Content-Type', 'text/plain');
    $response->end();
});

// Noop for task workers, but the callbacks must be registered
$server->on('task', function () {});
$server->on('finish', function () {});

$server->on('workerstart', function (Server $server, int $workerId) {
    /**
     * AFAIK, 'workerstart' is called for every http worker and task worker.
     * In this callback, you can only tell them apart if the workerId exceeds the number of
     * defined http workers. I don't know what happens when a worker crashes and a new one is
     * spawned… yet…
     */
    if($workerId >= $server->setting['worker_num']) {
        setProcessName(sprintf('task-worker-%d', $workerId));
    } else {
        setProcessName(sprintf('http-worker-%d', $workerId));
    }
});

$server->start();
