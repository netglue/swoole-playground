<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;
use function NG\getCliPortNumberWithDefault;
use function NG\setProcessName;

setProcessName('http-server-parent-process');

$server = new Server('127.0.0.1', getCliPortNumberWithDefault(), SWOOLE_BASE);
$server->set([
    'worker_num' => 4,
]);
$server->on('start', function (Server $server) {
    setProcessName('http-server-master-process');
});

$server->on('request', function (Request $request, Response $response) {
    $response->status(200);
    $response->header('Content-Type', 'text/plain');
    $response->end();
});

$server->on('workerstart', function (Server $server, int $workerId) {
    setProcessName(sprintf('http-worker-%d', $workerId));
});

$server->start();
