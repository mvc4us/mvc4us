<?php

declare(strict_types=1);

namespace Mvc4us\DependencyInjection\Loader;

use Mvc4us\Routing\Loader\RouteLoader;
use Mvc4us\Twig\TwigLoader;
use Symfony\Component\Config\Exception\FileLocatorFileNotFoundException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

/**
 *
 * @author erdem
 * @internal
 */
final class ServiceContainerLoader
{

    /**
     * This class should not be instantiated.
     */
    private function __construct()
    {
    }

    public static function load($projectDir): ContainerInterface
    {
        $container = new ContainerBuilder();
        $serviceLocator = new FileLocator($projectDir . '/config/services');
        $serviceLoader = new PhpFileLoader($container, $serviceLocator);

        try {
            $serviceLoader->load('services.php');
        } catch (FileLocatorFileNotFoundException $e) {
            $definition = new Definition();
            $definition->setAutowired(true)->setAutoconfigured(true)->setPublic(true);
            $serviceLoader->registerClasses($definition, 'App\\', $projectDir . '/src/*', null);
        }

        $container->compile();

        $router = RouteLoader::load($projectDir);
        $container->set('router', $router);

        if (class_exists('Twig\\Environment')) {
            $twig = TwigLoader::load($projectDir);
            $container->set('twig', $twig);
        }

        return $container;
    }
}
