<?php
declare(strict_types=1);

namespace NG;

use function getopt;
use function is_numeric;
use function swoole_set_process_name;
use const PHP_OS;

/**
 * Returns a port number for the cli options -p or --port falling back to the default given
 * @param int $default
 * @return int
 */
function getCliPortNumberWithDefault(int $default = 8080) : int
{
    $opt = getopt('p:', ['port:']);
    $port = isset($opt['p']) && is_numeric($opt['p']) ? (int) $opt['p'] : $default;
    $port = isset($opt['port']) && is_numeric($opt['port']) ? (int) $opt['port'] : $port;
    return $port;
}

/**
 * Calls swoole_set_process_name without the warnings for MacOS
 * @param string $name
 */
function setProcessName(string $name) : void
{
    // swoole_set_process_name does not work on a Mac and issues warnings
    if (PHP_OS === 'Darwin') {
        return;
    }
    swoole_set_process_name($name);
}
