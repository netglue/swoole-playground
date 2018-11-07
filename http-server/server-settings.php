<?php
declare(strict_types=1);

namespace NG;

use Swoole\Http\Server as HttpServer;
use function printf;
use function var_export;
use const SWOOLE_BASE;

/**
 * This script illustrates that repeated calls to $httpServer->set() do not replace all previous settings.
 * Internally, Swoole must be merging received settings with any that already exist.
 */

$dumpSettings = function (HttpServer $server) {
    printf(
        "%s\n",
        var_export($server->setting, true)
    );
};

$server = new HttpServer('127.0.0.1', 9123, SWOOLE_BASE);

$server->set([
    'daemonize' => false,
]);
$dumpSettings($server);
$server->set([
    'log_file' => '/tmp/foo.log',
]);
$dumpSettings($server);
$server->set([
    'pid_file' => '/tmp/foo.pid',
    'log_file' => '/tmp/foo-bar.log',
]);
$dumpSettings($server);
$server->set([
    'pid_file' => null,
]);
$dumpSettings($server);
