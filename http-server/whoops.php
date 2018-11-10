<?php
declare(strict_types=1);

namespace NG;

use function get_class;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server as HttpServer;
use Throwable;
use Whoops;
use const SWOOLE_BASE;

require_once __DIR__ . '/../vendor/autoload.php';

$server = new HttpServer('127.0.0.1', 8080, SWOOLE_BASE);
$server->set([
    'worker_num' => 1,
]);

$handleRequest = function (Request $request, Response $response) : void
{
    throw new \Exception('FFS!');
};

/**
 * So Using whoops is all good in Swoole, I ended up doing this due to an issue in Zend Expressive
 * https://github.com/zendframework/zend-expressive/issues/635
 * Anyhow, the pretty page handler just refuses to render anything if PHP_SAPI === 'cli' unless, you hit
 * $handler->renderUnconditionally(true)
 */
$server->on('Request', function (Request $request, Response $response) use ($handleRequest) {

    $whoops = new Whoops\Run();
    $handler = new Whoops\Handler\PrettyPageHandler();
    $handler->handleUnconditionally(true);
    $whoops->pushHandler($handler);
    $whoops->sendHttpCode(false);
    $whoops->allowQuit(false);
    $whoops->writeToOutput(false);
    $whoops->register();
    try {
        $handleRequest($request, $response);
    } catch (Throwable $exception) {
        try {
            $output = $whoops->handleException($exception);
        } catch (Throwable $error) {
            $output = printf(
                "Code: %d\nMessage:%s\nClass:%s\nTrace:%s",
                $error->getCode(),
                $error->getMessage(),
                get_class($error),
                $error->getTraceAsString()
            );
        }
        $response->header('Content-Type', 'text/html');
        $response->status(500);
        $response->write($output);
        $response->end();
    }
});

$server->on('Start', function (HttpServer $server) {
    printf("Server running at http://%s:%d\n", $server->host, $server->port);
});

$server->start();

exit(0);
