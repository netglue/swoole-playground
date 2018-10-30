<?php
declare(strict_types=1);

use Swoole\Event;
use Swoole\Process;

/**
 * This script fires up a child process.
 * Once it's running, in another terminal, send a signal to the child process PID of SIGTERM or SIGUSR1
 * For example `kill -s SIGUSR1 1234` where 1234 is the child's PID
 */


/**
 * This function defines the behaviour of the child process
 *
 * @param Process $process
 */
$childProcess = function(Process $process) {
    // Write a message to mater process:
    $process->write('Starting Up');
    // Listen for a SIGTERM to this child process, fire off a message and exit cleanly
    Process::signal(SIGTERM, function (int $sigNum) use ($process) {
        $process->write(sprintf(
            'Process %d Received SIGTERM (%d). Exiting…',
            $process->pid,
            $sigNum
        ));
        $process->close();
        $process->exit(SIGTERM);
    });
    // Listen for a SIGUSR1 sent to the child process and report it
    Process::signal(SIGUSR1, function (int $sigNum) use ($process) {
        $process->write(sprintf(
            'Process %d Received SIGUSR1 (%d)',
            $process->pid,
            $sigNum
        ));
    });
    // When the master sends a message down the child's pipe, print it to the terminal
    Event::add($process->pipe, function ($pipe) use ($process) {
        $received = $process->read();
        printf("Received from master: %s\n", $received);
    });
    // Let master know that we're done
    $process->write('Finished Setting Up');
};

/**
 * We're creating the process here because we need it in the following handlers
 */
$process = new Process($childProcess);

/**
 * Starting it here is irrelevant. You could start the process after the event handlers if you want
 */
$process->start();

/**
 * Respond to messages from the child process
 */
Event::add($process->pipe, function ($pipe) use ($process) {
    printf("Received from child: %s\n", $process->read());
});

/**
 * Handle the SIGCHLD signal - this is received in the parent process when a child dies
 */
Process::signal(SIGCHLD, function (int $sigNum) use ($process) {
    /**
     * If Process::wait() is non-blocking, then you'd have to wait inside a loop.
     * Also, if there's more than 1 child process, you'd do this differently as you might need
     * to manage the exit of several child processes.
     */

    // $sigNum will always be 20 (SIGCHLD)
    printf("Master received SIGCHLD with signal argument %d\n", $sigNum);
    //while (1) {
        $childExitStatus = Process::wait(true);
        if ($childExitStatus) {
            /**
             * The exit code will be whatever was set in $process->exit(), IF, it was called
             * The signal will be whatever signal was sent to the child. This could be *any* signal.
             * It appears that if a signal is handled by the child, then the signal will be set to zero
             */
            printf(
                "Child exited on signal %d with status %d\n",
                $childExitStatus['signal'],
                $childExitStatus['code']
            );
            if ($childExitStatus['signal'] === SIGABRT) {
                echo "Got a SIGABRT. We’re done here…\n";
                Process::kill(getmypid(), SIGABRT);
            }
            $process->start();
            printf(
                "Restarted child process with pid %d\n",
                $process->pid
            );
            $process->write('I restarted you!');
        } else {
            //break;
        }
    //}
});

printf("My PID is %d. Child Process started with PID %d\n", getmypid(), $process->pid);
print 'Hit CRTL + C to exit this process and terminate the child process too' . PHP_EOL;
printf("Send a signal to the child in another terminal like this: kill -s TERM %d\n", $process->pid);
/**
 * This is the end.
 * If we setup any kind of blocking operation here, the whole thing falls on it's ass.
 * I'm not sure why… The child process starts but none of the signal handlers do anything and none of the pipes are
 * read from or written to. Uncomment the while loop below to see this in action:
 */
//while (true) {
//    // Noop
//}
