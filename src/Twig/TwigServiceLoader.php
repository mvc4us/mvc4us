<?php

declare(strict_types=1);

namespace Mvc4us\Twig;

use Mvc4us\Config\Config;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * @internal
 */
final class TwigServiceLoader
{
    private function __construct()
    {
    }

    public static function load(ContainerBuilder $container, string $projectDir): void
    {
        if (!class_exists('Twig\Environment')) {
            return;
        }

        //TODO: set default options here but overwrite them from custom configuration.
        $templateDir = $projectDir . '/templates';
        $cacheDir = $projectDir . '/var/cache/twig';
        $options = [
            'cache' => $cacheDir,
            'auto_reload' => Config::isDebug()
        ];
        if (!file_exists($templateDir)) {
            mkdir(directory: $templateDir, recursive: true);
        }
        if (!file_exists($cacheDir)) {
            mkdir(directory: $cacheDir, recursive: true);
        }
        $loader = new FilesystemLoader($templateDir);

        $container->register(Environment::class)
            ->setArgument('$loader', $loader)
            ->setArgument('$options', $options);
        $container->setAlias('twig', Environment::class)->setPublic(true);
    }
}
