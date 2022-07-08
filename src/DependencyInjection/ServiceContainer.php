<?php

declare(strict_types=1);

namespace Mvc4us\DependencyInjection;

use Mvc4us\DependencyInjection\Loader\RouteServiceLoader;
use Mvc4us\DependencyInjection\Loader\SerializerServiceLoader;
use Mvc4us\DependencyInjection\Loader\TwigServiceLoader;
use Mvc4us\MiddleWare\AfterControllerInterface;
use Mvc4us\MiddleWare\BeforeControllerInterface;
use Mvc4us\MiddleWare\BeforeMatcherInterface;
use Symfony\Component\Config\Exception\FileLocatorFileNotFoundException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\TaggedContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @author erdem
 * @internal
 */
final class ServiceContainer
{

    /**
     * This class should not be instantiated.
     */
    private function __construct()
    {
    }

    public static function load($projectDir): TaggedContainerInterface
    {
        $container = new ContainerBuilder();
        $serviceLocator = new FileLocator($projectDir . '/config');
        $serviceLoader = new PhpFileLoader($container, $serviceLocator);

        $container->registerForAutoconfiguration(BeforeMatcherInterface::class)
            ->addTag('app_before_matcher')
            ->setPublic(true);
        $container->registerForAutoconfiguration(BeforeControllerInterface::class)
            ->addTag('app_before')
            ->setPublic(true);
        $container->registerForAutoconfiguration(AfterControllerInterface::class)
            ->addTag('app_after')
            ->setPublic(true);
        try {
            $serviceLoader->load('services.php');
        } catch (FileLocatorFileNotFoundException) {
            $definition = new Definition();
            $definition->setAutowired(true)->setAutoconfigured(true)->setPublic(true);
            $serviceLoader->registerClasses($definition, 'App\\', $projectDir . '/src/*');
            error_log(
                "File '/config/services.php' not found. All container objects defined as public 'App\\' => '$projectDir/src/*'."
            );
        } catch (\Exception $e) {
            error_log('Unexpected exception. ' . $e);
        }

        $container->register(RequestStack::class);
        $container->setAlias('request_stack', RequestStack::class)->setPublic(true);

        RouteServiceLoader::load($container, $projectDir);
        TwigServiceLoader::load($container, $projectDir);
        SerializerServiceLoader::load($container);

        $container->compile();
        return $container;
    }
}
