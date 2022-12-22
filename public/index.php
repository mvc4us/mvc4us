<?php

declare(strict_types=1);

use Mvc4us\Mvc4us;

/**
 * Absolute application path
 */
define('APP_DIR', dirname(__DIR__));

require APP_DIR . '/vendor/autoload.php';

$mvc4us = new Mvc4us(APP_DIR);
$mvc4us->runWeb();
