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
class NonRedirectingCompiledUrlMatcher extends CompiledUrlMatcher implements RedirectableUrlMatcherInterface
{

    /**
     *
     * {@inheritdoc}
     */
    public function redirect(string $path, string $route, string $scheme = null): array
    {
        return [
            'scheme' => $scheme,
            '_route' => $route
        ];
    }
}
