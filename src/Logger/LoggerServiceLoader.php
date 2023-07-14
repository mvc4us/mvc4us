<?php
declare(strict_types=1);

namespace Mvc4us\Logger;

use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @internal
 */
class LoggerServiceLoader
{
    public static function load(ContainerBuilder $container): void
    {
        $id = class_exists('Monolog\Logger') ? Logger::class : AdhocLogger::class;
        $container->register($id)->setFactory([LoggerConfig::class, 'getInstance']);
        $container->setAlias(LoggerInterface::class, $id);
        $container->setAlias('logger', $id)->setPublic(true);
    }
}
