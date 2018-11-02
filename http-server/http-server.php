<?php
declare(strict_types=1);
namespace NG;

use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server as HttpServer;
use function var_export;
use const PHP_EOL;
use const STDOUT;
use const SWOOLE_PROCESS;

$httpServer = new HttpServer('127.0.0.1', 9001, SWOOLE_PROCESS);

$httpServer->on('start', function(HttpServer $server) {
    fwrite(STDOUT, sprintf(
        'HTTP Server up and running at %s:%d' . PHP_EOL,
        $server->host,
        $server->port
    ));
});

$httpServer->on('request', function(Request $request, Response $response) {
    $response->status(200);
    $response->write('Hello!');
    $response->write('<pre>');
    $response->write(var_export($request, true));
    $response->write('</pre>');
});

$httpServer->start();

