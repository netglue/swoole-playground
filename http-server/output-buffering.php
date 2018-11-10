<?php
declare(strict_types=1);

namespace NG;

use function printf;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server as HttpServer;
use const SWOOLE_BASE;

$server = new HttpServer('127.0.0.1', 8080, SWOOLE_BASE);
$server->set([
    'worker_num' => 1,
]);

$server->on('Request', function (Request $request, Response $response) {
    ob_start();
    echo 'This was buffered using PHP’s ob_* functions.' . PHP_EOL;
    $content = ob_get_clean();

    $response->write(<<<EOF
Output Buffering Captured the following content:
{$content}
EOF
);
    $response->status(200);
    $response->header('Content-Type', 'text/plain');
    $response->end();
});

$server->on('Start', function (HttpServer $server) {
    printf("Server started at http://%s:%d\n", $server->host, $server->port);
});

$server->start();

exit(0);
