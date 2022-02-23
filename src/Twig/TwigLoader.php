<?php

declare(strict_types=1);

namespace Mvc4us\Twig;

use Mvc4us\Config\Config;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * @internal
 */
final class TwigLoader
{

    private function __construct()
    {
    }

    public static function load(ContainerInterface $container, string $projectDir): void
    {
        if (!class_exists('Twig\\Environment')) {
            return;
        }

        //TODO: set default options here and overwrite them from custom configuration.
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
        $twig = new Environment($loader, $options);
        $container->set('twig', $twig);
    }
}

