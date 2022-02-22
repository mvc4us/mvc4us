<?php

declare(strict_types=1);

namespace Mvc4us\Routing;

use Symfony\Component\Routing\Matcher\CompiledUrlMatcher;
use Symfony\Component\Routing\Matcher\RedirectableUrlMatcherInterface;

/**
 *
 * @author erdem
 * @internal
 */
class RedirectingCompiledUrlMatcher extends CompiledUrlMatcher implements RedirectableUrlMatcherInterface
{

    /**
     *
     * {@inheritdoc}
     */
    public function redirect(string $path, string $route, string $scheme = null): array
    {
        // TODO: create RedirectController
        return [
            '_controller' => 'RedirectController',
            'path' => $path,
            'permanent' => true,
            'scheme' => $scheme,
            'httpPort' => $this->context->getHttpPort(),
            'httpsPort' => $this->context->getHttpsPort(),
            '_route' => $route
        ];
    }
}
