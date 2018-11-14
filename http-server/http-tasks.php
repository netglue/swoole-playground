<?php
declare(strict_types=1);

use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;

$httpServer = new Server('127.0.0.1', 9001, SWOOLE_PROCESS);
$httpServer->set([
    'worker_num' => 1,      // Http Worker Count
    'task_worker_num' => 1, // Task Worker Count
    'daemonize' => 0, // We don't want to daemonize (The default). You'll need to keep track of the pid if you daemonize
                      // so that you can easily kill the server. You'll also need to setup logging for STDOUT/ERR
                      //
]);

/**
 * These 2 functions are our actual tasks. They do a lot of sleeping, like 10 or 20 seconds of it. This very clearly
 * shows that the tasks are performed in a different thread to the HTTP server and it's non-task workers.
 *
 * However your tasks are setup and whatever they do, they are not required to return anything, but they might
 * if you want to use the value in your 'onTask' callback
 */
$task1Job = function (int $maybe, int $some, int $args) {
    /**
     * Doing something complicated. And slow here.
     */
    sleep(10);
    /**
     * Note that we are returning a value
     */
    return $maybe + $some + $args;
};

$task2Job = function() {
    echo "Job 2 starting\n";
    sleep(20);
    echo "Job 2 finished, but I’m not returning anything\n";
};

/**
 * This function is triggered whenever something calls $httpServer->task($data)
 *
 * The $data param in $httpServer->task($data) is the 4th $data param given to this callback.
 *
 * If this function does not return anything, then the 'onFinish' callback will not be executed.
 * onFinish is executed with whatever you return as it's 3rd parameter
 */
$onTask = function (Server $server, int $taskId, int $sourceWorkerId, $data) use ($task1Job, $task2Job) {
    printf("Starting Task ID #%d initiated by task worker #%d\n", $taskId, $sourceWorkerId);
    /**
     * This is our task router that inspects $data and performs the right task
     */
    switch ($data) {
        case 'task-1':
            print("Task Router matched to Job 1\n");
            return $task1Job(random_int(1,10), random_int(10, 100), random_int(0, 500));
            break;
        case 'task-2':
            print("Task Router matched to Job 2\n");
            $task2Job();
            return 'Job 2 finished';
            break;
        case 'task-3':
            return 1; // What happens if you return an int?
            break;
        case 'task-4':
            return json_decode('{"foo":"bar"}');
            break;
        default:
            printf("No matching Task\n");
            // return nothing
            break;
    }
};

/**
 * This function is only executed when a value is returned from our onTask event handler
 *
 * The $data param is marked as (string) in some places but it appears you can throw anything you like at it.
 */
$onFinish = function (Server $server, int $taskId, $data) {
    printf(
        "A task has finished. The task ID that finished was #%d and the task returned the data: (%s) %s\n",
        $taskId,
        gettype($data),
        json_encode($data)
    );
};

/**
 * On Startup, Let us know where we're running…
 */
$httpServer->on('start', function(Server $server) {
    fwrite(STDOUT, sprintf(
        'HTTP Server up and running at http://%s:%d' . PHP_EOL,
        $server->host,
        $server->port
    ));
});

$dumpRequestInfo = function(Request $request, Response $response) {
    $response->write(sprintf('<pre>%s</pre>', var_export($request->server, true)));
};

/**
 * This is our request handler - we're just reading from the request and writing to the response here:
 */
$httpServer->on('request', function(Request $request, Response $response) use ($dumpRequestInfo, $httpServer) {
    $response->header('Server', 'Whatever');
    $response->header('Content-Type', 'text/html');
    $response->write('<h2>Request Info</h2>');
    $dumpRequestInfo($request, $response);
    $response->write('
        <ul>
            <li><a href="/">Home</a></li>
            <li><a href="/task-1">Trigger Task 1</a></li>
            <li><a href="/task-2">Trigger Task 2</a></li>
            <li><a href="/task-3">Trigger Task 3</a></li>
            <li><a href="/task-4">Trigger Task 4</a></li>
        </ul>'
    );

    /**
     * You can route tasks to specific workers by specifying their ID.
     * Omitting the id or using -1 as the worker id means that the server will pick whichever it likes.
     */
    $workerId = -1;

    /**
     * Triggering tasks is async/non-blocking
     */
    switch($request->server['request_uri']) {
        case '/task-1':
            // Send anything you want as the 'data' parameter
            $httpServer->task('task-1', $workerId);
            break;
        case '/task-2':
            $httpServer->task('task-2', $workerId);
            break;
        case '/task-3':
            $httpServer->task('task-3', $workerId);
            break;
        case '/task-4':
            $httpServer->task('task-4', $workerId);
            break;
        default:
            break;
    }
});

/** Attach the task event handlers defined earlier */
$httpServer->on('task', $onTask);
$httpServer->on('finish', $onFinish);

/** Start the server */
$httpServer->start();
