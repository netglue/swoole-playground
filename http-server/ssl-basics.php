<?php
declare(strict_types=1);

namespace NG;

use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server as HttpServer;
use function defined;
use function printf;
use const PHP_EOL;
use const SWOOLE_BASE;
use const SWOOLE_SOCK_TCP;
use const SWOOLE_SSL;

/**
 * SWOOLE_SSL is only defined if the swoole module has been built with OpenSSL Support
 */
if (! defined('SWOOLE_SSL')) {
    echo 'Swoole has not been compiled with SSL Support' . PHP_EOL;
    exit(1);
}

/**
 * The server will refuse to start without 'ssl_cert_file' and 'ssl_key_file' when enabling SSL
 */
$server = new HttpServer('127.0.0.1', 8080, SWOOLE_BASE, SWOOLE_SOCK_TCP | SWOOLE_SSL);
$server->set([
    'worker_num' => 1,
    'ssl_cert_file' => __DIR__ . '/../ssl/server.crt',
    'ssl_key_file' => __DIR__ . '/../ssl/server.key',
]);
$server->on('Start', function (HttpServer $server) {
    printf("Server started at https://%s:%d\n", $server->host, $server->port);
    printf("The cert will be invalid, so try `curl -k https://%s:%d`\n", $server->host, $server->port);
});
$server->on('Request', function (Request $request, Response $response) {
    $response->status(200);
    $response->header('Content-Type', 'text/plain');
    $response->write('Successful SSL Request/Response' . PHP_EOL);
    $response->end();
});
$server->start();

exit(0);
