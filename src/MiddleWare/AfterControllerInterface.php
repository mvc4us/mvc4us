<?php
declare(strict_types=1);

namespace Mvc4us\MiddleWare;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

interface AfterControllerInterface
{
    /**
     * * MIDDLEWARES MUST NOT THROW EXCEPTION.
     * * If this returns true regular flow will continue.
     * * If this returns false, all remaining middlewares will be skipped then the response will be processed.
     *
     * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
     * @param \Symfony\Component\HttpFoundation\Response $response
     * @return bool
     */
    public function processAfter(RequestStack $requestStack, Response $response): bool;
}
