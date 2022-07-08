<?php
declare(strict_types=1);

namespace Mvc4us\MiddleWare;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

interface BeforeControllerInterface
{
    /**
     * * MIDDLEWARES MUST NOT THROW EXCEPTION.
     * * If this returns null regular flow will continue.
     * * If this returns a \Symfony\Component\HttpFoundation\Response instance, all remaining middlewares and
     * the controller will be skipped then the response will be processed.
     *
     * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
     * @return \Symfony\Component\HttpFoundation\Response|null
     */
    public function processBefore(RequestStack $requestStack): ?Response;
}
