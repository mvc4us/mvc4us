<?php

declare(strict_types=1);

namespace Mvc4us\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface ControllerInterface
{

    /**
     * Handle request.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response|null
     */
    public function handle(Request $request): ?Response;
}
