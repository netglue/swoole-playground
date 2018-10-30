<?php
declare(strict_types=1);

use Swoole\Process;

/**
 * Waiting for the exit of a child process.
 */

$childProcess = new Process(function() {
   usleep(100);
   echo "Child has woken up\n";
   for ($i = 0; $i <= 5; $i++) {
       echo "Child is sleeping\n";
       sleep(1);
   }
   echo "Child has finished sleeping\n";
});

$childProcess->start();
printf("My PID is %d and the child is running with process ID %d\n", getmypid(), $childProcess->pid);

$blocking = true;
$childExitStatus = Process::wait($blocking);

echo "The exit status/information about the child process:\n";
print_r($childExitStatus);
echo PHP_EOL;
