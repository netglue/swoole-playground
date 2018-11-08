<?php
declare(strict_types=1);

namespace NG;

use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server as HttpServer;
use function json_encode;
use function printf;
use function time;
use function var_export;
use const JSON_PRETTY_PRINT;
use const SWOOLE_BASE;

/**
 * So this is the actual work…
 */
$heavyLifting = function () : string {
    sleep(5);
    return 'Finished the heavy lifting';
};

/**
 * Our 'onTask' callback for server - This callback triggers the 'Actual Work'
 * $userData can be anything you like, except a resource
 */
$serverOnTaskCallback = function (HttpServer $server, int $taskId, int $sourceWorkerId, string $userData) use ($heavyLifting) {
    /**
     * Record a bunch of information in an array, trigger the actual work and return some info
     * as a json_encoded array
     */
    $taskInfo = [
        'startTime' => time(),
        'userDataReceived' => $userData,
        'taskId' => $taskId,
        'workerId' => $sourceWorkerId,
    ];
    $taskInfo['result'] = $heavyLifting();
    $taskInfo['endTime'] = time();
    return json_encode($taskInfo);
};

/**
 * This callback is triggered when a task work has finished a task. The $userData param
 * is whatever was _returned_ by the 'onTask' callback
 */
$serverTaskFinishedCallback = function (HttpServer $server, int $taskId, $userData) {
    $taskCompletionResult = json_decode($userData, true);
    printf(
        "A task has been completed:\n%s\n",
        var_export($taskCompletionResult, true)
    );
};

// Create server and register callbacks
$server = new HttpServer('127.0.0.1', 8080, SWOOLE_BASE);
$server->on('Task', $serverOnTaskCallback);
$server->on('Finish',  $serverTaskFinishedCallback);
// We need 'task_worker_num'
$server->set([
    'task_worker_num' => 1, // Task Worker Count
    'worker_num' => 1, // HTTP Server Worker Count
]);

/**
 * Our onRequest handler triggers a task for any request and returns a json encoded response to the http client
 */
$onServerRequest = function (Request $request, Response $response) use ($server) {
    $taskId = $server->task('My User Data for the Task Worker');
    $response->status(200);
    $response->header('TaskId', (string) $taskId);
    $response->header('Content-Type', 'application/json');
    $response->write(json_encode([
        'Result' => 'Alrighty Then!'
    ], JSON_PRETTY_PRINT) . PHP_EOL);
    $response->end();
    echo "\n--- Request Received, Response Sent ---\n\n";
};
$server->on('Request', $onServerRequest);

/**
 * Dump some stuff to the console on start:
 */
$server->on('Start', function (HttpServer $server) {
    printf(
        "Server has started at http://%s:%d\nHit this URL with curl in another terminal twice and wait around 5 seconds for some output.\n",
        $server->host,
        $server->port
    );
});

$server->start();

exit(0);
