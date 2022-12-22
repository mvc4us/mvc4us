<?php
declare(strict_types=1);

namespace Mvc4us\MiddleWare;

interface MiddlewareConstants
{
    public const BEFORE_MATCHER = 'mw_before_matcher';
    public const BEFORE_CONTROLLER = 'mw_before_controller';
    public const AFTER_CONTROLLER = 'mw_after_controller';
}
