<?php
declare(strict_types=1);

namespace Mvc4us\MiddleWare;

/**
 * DO NOT IMPLEMENT THIS INTERFACE DIRECTLY.
 * This is a base interface which all middleware interfaces MUST extend.
 *
 * @internal
 */
interface MiddlewareInterface
{
    public const BEFORE_MATCHER = 'mw_before_matcher';
    public const BEFORE_CONTROLLER = 'mw_before_controller';
    public const AFTER_CONTROLLER = 'mw_after_controller';

    /**
     * This method is used to prioritize run order. Higher value makes middleware run sooner.
     *
     * @return int
     */
    public function getPriority(): int;
}
