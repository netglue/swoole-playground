<?php
declare(strict_types=1);

namespace NG;

use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;
use function sleep;
use const PHP_EOL;

$iGotJobsToDo = function (string $hitMe) : string
{
    echo 'I\'m doing a job… with ' . $hitMe . PHP_EOL;
    sleep(2);
    return 'Work is done';
};

$server = new Server('127.0.0.1', 8080, SWOOLE_BASE);
$server->set([
    'worker_num' => 1,
    'task_worker_num' => 1,
]);

$server->on('Request', function (Request $request, Response $response) use ($server) {

    $response->status(200);
    $response->header('Content-Type', 'text/plain');
    // Response is sent to the client here are they disconnect,
    // but, the rest of this function body continues to execute.
    $response->end();

    echo 'Starting taskWait() at ' . date('H:i:s') . PHP_EOL;
    $secondsTimeout = 10;
    /**
     * As you'd expect, ->taskwait() blocks, so if you haven't already sent the response,
     * with $response->end(), the http client is still waiting.
     */
    $server->taskwait('[Sync]', $secondsTimeout);
    echo 'Finished taskWait() at ' . date('H:i:s')  . PHP_EOL;

    $server->task('[Async]');
});

$server->on('Start', function(Server $server) {
    printf(
        'HTTP Server up and running at http://%s:%d' . PHP_EOL,
        $server->host,
        $server->port
    );
    printf(
        'call `curl -I http://%s:%d` in another terminal to trigger a request' . PHP_EOL,
        $server->host,
        $server->port
    );
});

$server->on('Task', function (Server $server, int $taskId, int $sourceWorkerId, $data) use ($iGotJobsToDo) {
    $return = $iGotJobsToDo($data);
    /**
     * For synchronous tasks, you *should* either return something or call $server->finish(). If you don't the
     * main process will block until the timeout set in $server->taskwait() has elapsed. Regardless, the task finish
     * event is never called.
     *
     * For asynchronous tasks, you should do one of return something, or call $server->finish() if you want the task
     * finish event to fire. If you do neither, it doesn't appear to be a problem but there's no on('Finish') event.
     * If you do both, the finish event will be fired twice.
     */
    $server->finish($return);
    //return $return;
});

/**
 * The task finished event here is triggered under the following conditions:
 *
 * For async $server->task()
 * - YES if on('Task') returns something
 * - TWICE if you return something in on('Task') AND call $server->finish()
 * - YES, ONCE if you don't return anything on('Task') and call $server->finish()
 * - NO, if you neither return nor call finish()
 *
 * For sync $server->taskwait()
 * - NO if on('Task') DOES return something and finish() is not called
 * - NO if nothing is returned and you call finish()
 * - NO if you neither call finish, nor return (In this case, the main process blocks for the duration of $timeout)
 * - NO if you BOTH return and call finish() - In this case a warning is raised with "task[id] has expired"
 */
$server->on('Finish', function (Server $server, int $taskId, $data) {
    printf('Finish Event Called with data: %s%s', $data, PHP_EOL);
    return 'Finish Event Done';
});

$server->start();
