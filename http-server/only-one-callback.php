<?php
declare(strict_types=1);

namespace NG;

use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server as HttpServer;
use const PHP_EOL;
use const SWOOLE_BASE;

/**
 * This probably goes without saying, but each successive call to $server->on() with the same
 * event name, will overwrite the previous assigned callback.
 */

$server = new HttpServer('127.0.0.1', 6789, SWOOLE_BASE);
$server->set([
    'worker_num' => 1,
]);
$server->on('Request', function (Request $request, Response $response) {
    $response->write('How Do?' . PHP_EOL);
    $response->status(200);
    $response->header('Content-Type', 'text/plain');
    $response->end();
});
$server->on('Start', function () {
    echo 'Call Back One Has Fired…' . PHP_EOL;
});
$server->on('Start', function () {
    echo 'Call Back Two Has Fired…' . PHP_EOL;
});

$server->start();

exit(0);