<?php

declare(strict_types=1);

/**
 * Services configuration is achieved by Symfony's dependency injection component.
 * This is a sample file which consists of common options which may be widely need.
 *
 * @link https://symfony.com/doc/current/service_container.html
 * @link https://symfony.com/doc/current/service_container/import.html
 * @link https://symfony.com/doc/current/components/dependency_injection.html
 */
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $configurator, ContainerBuilder $container, PhpFileLoader &$loader) {

    /*
     * Setting default options for services.
     */
    $services = $configurator->services()->defaults()->autoconfigure()->autowire();

    /*
     * Class Registration.
     */
    $services->load('App\\', '../../src/*');

    /*
     * Controller Registration. Those have to be public
     */
    $services->load('App\\Controller\\', '../../src/Controller/*')->public();
};
