<?php
declare(strict_types=1);

namespace NG;

use Swoole\Timer;
use function microtime;
use function printf;
use const PHP_EOL;

$ticks = 0;

$resolution = 100; // Milliseconds

$timerId = Timer::tick($resolution, function(int $timerId) use (&$ticks) {
    printf(
        'Tick#%d: %0.3f' . PHP_EOL,
        $ticks,
        microtime(true)
    );
    $ticks++;
    if ($ticks >= 10) {
        print('Stopping timer after 10 ticks' . PHP_EOL);
        Timer::clear($timerId);
    }
});

// The timer is async so control is released straight to here, before the first tick even fires.
printf('Starting Timer with ID %d' . PHP_EOL, $timerId);

// Similarly, the Timer::after method will relinquish control straight back to you too.

$afterId = Timer::after( $resolution * 4, function () use ($resolution) {
    printf('Fired Timer after %dms' . PHP_EOL, $resolution * 4);
});

printf('Starting Timer with ID %d' . PHP_EOL, $afterId);

// This script exits cleanly once the timer has been cancelled, or on interrupt.
// If the timer finishes all its work and we end up here, we'll get an exit status of zero.
// If an interrupt is received, like CTRL+C, the exit will be 130.
// @see http://tldp.org/LDP/abs/html/exitcodes.html


