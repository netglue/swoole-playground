<?php
declare(strict_types=1);

use Swoole\Event;
use Swoole\Process;

$process = new Process(function (Process $process) {
    $process->write('Ohhh I do like to be');
    Event::add($process->pipe, function ($pipe) use ($process) {
        $message = $process->read();
        printf("Master -> Child: '%s'\n", $message);
        $process->exit(); // Terminate the child process after 1 message is received
    });
});

Event::add($process->pipe, function() use ($process) {
    $message = $process->read();
    printf("Child -> Master: '%s'\n", $message);
});
$process->start();

printf('Child Process started with PID %d' . PHP_EOL, $process->pid);
$process->write('beside the seaside…');

Process::signal(SIGCHLD, function() {
    Process::kill(getmypid(), SIGTERM);
});
