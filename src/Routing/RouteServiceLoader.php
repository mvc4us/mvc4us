<?php

declare(strict_types=1);

namespace Mvc4us\Routing;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Routing\Loader\AnnotationDirectoryLoader;
use Symfony\Component\Routing\Loader\PhpFileLoader;
use Symfony\Component\Routing\Router;

/**
 * @author erdem
 * @internal
 */
final class RouteServiceLoader
{
    private function __construct()
    {
    }

    public static function load(ContainerBuilder $container, string $projectDir): void
    {
        $resolver = new LoaderResolver();
        $resolver->addLoader(new PhpFileLoader(new FileLocator($projectDir . '/config')));
        $resolver->addLoader(new AnnotationDirectoryLoader(new FileLocator(), new AnnotatedRouteLoader()));
        $routeLoader = new DelegatingLoader($resolver);

        // TODO: Implement configurable redirection
        $container->register(Router::class)
            ->setArgument('$loader', $routeLoader)
            ->setArgument('$resource', 'routes.php')
            ->setArgument('$options', [
                'matcher_class' => NonRedirectingCompiledUrlMatcher::class
                // 'matcher_class' => RedirectingCompiledUrlMatcher::class
            ]);
        $container->setAlias('router', Router::class)->setPublic(true);
    }
}

