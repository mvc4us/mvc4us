<?php

declare(strict_types=1);

namespace Mvc4us\DependencyInjection;

use Mvc4us\Logger\LoggerConfig;
use Mvc4us\Logger\LoggerServiceLoader;
use Mvc4us\MiddleWare\AfterControllerInterface;
use Mvc4us\MiddleWare\BeforeControllerInterface;
use Mvc4us\MiddleWare\BeforeMatcherInterface;
use Mvc4us\MiddleWare\MiddlewareInterface;
use Mvc4us\Routing\RouteServiceLoader;
use Mvc4us\Serializer\SerializerServiceLoader;
use Mvc4us\Twig\TwigServiceLoader;
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

    public static function load($projectPath): TaggedContainerInterface
    {
        $container = new ContainerBuilder();
        $serviceLocator = new FileLocator($projectPath . '/config');
        $serviceLoader = new PhpFileLoader($container, $serviceLocator);

        $container->registerForAutoconfiguration(BeforeMatcherInterface::class)
            ->addTag(MiddlewareInterface::BEFORE_MATCHER)
            ->setPublic(true);
        $container->registerForAutoconfiguration(BeforeControllerInterface::class)
            ->addTag(MiddlewareInterface::BEFORE_CONTROLLER)
            ->setPublic(true);
        $container->registerForAutoconfiguration(AfterControllerInterface::class)
            ->addTag(MiddlewareInterface::AFTER_CONTROLLER)
            ->setPublic(true);
        try {
            $serviceLoader->load('services.php');
        } catch (FileLocatorFileNotFoundException) {
            $definition = new Definition();
            $definition->setAutowired(true)->setAutoconfigured(true)->setPublic(true);
            $serviceLoader->registerClasses($definition, 'App\\', $projectPath . '/src/*');
            LoggerConfig::getInstance()->notice(
                "File '/config/services.php' not found. All container objects defined as public 'App\\' => '$projectPath/src/*'."
            );
        } catch (\Exception $e) {
            LoggerConfig::getInstance()->error('Unexpected exception. ' . $e);
        }

        $container->register(RequestStack::class);
        $container->setAlias('request_stack', RequestStack::class)->setPublic(true);

        LoggerServiceLoader::load($container);
        RouteServiceLoader::load($container, $projectPath);
        TwigServiceLoader::load($container, $projectPath);
        SerializerServiceLoader::load($container);

        $container->compile();
        return $container;
    }
}
