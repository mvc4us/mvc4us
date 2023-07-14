<?php

declare(strict_types=1);

use Mvc4us\Mvc4us;

require dirname(__DIR__) . '/vendor/autoload.php';

$mvc4us = new Mvc4us(dirname(__DIR__));
$mvc4us->runWeb();
