<?php

declare(strict_types=1);

namespace Mvc4us\DependencyInjection\Loader;

use Mvc4us\Routing\Loader\RouteLoader;
use Mvc4us\Serializer\SerializerLoader;
use Mvc4us\Twig\TwigLoader;
use Symfony\Component\Config\Exception\FileLocatorFileNotFoundException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

/**
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
        $serviceLocator = new FileLocator($projectDir . '/config');
        $serviceLoader = new PhpFileLoader($container, $serviceLocator);

        try {
            $serviceLoader->load('services.php');
        } catch (FileLocatorFileNotFoundException $e) {
            $definition = new Definition();
            $definition->setAutowired(true)->setAutoconfigured(true)->setPublic(true);
            $serviceLoader->registerClasses($definition, 'App\\', $projectDir . '/src/*', null);
            error_log(
                "File '/config/services.php' not found. All container objects defined as public 'App\\' => '${projectDir}/src/*'."
            );
        }

        $container->compile();

        RouteLoader::load($container, $projectDir);

        TwigLoader::load($container, $projectDir);

        SerializerLoader::load($container);

        return $container;
    }
}
