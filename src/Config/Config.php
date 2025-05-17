<?php

declare(strict_types=1);

namespace Mvc4us\Config;

use Composer\InstalledVersions;
use Mvc4us\Utils\ArrayUtils;
use Yosymfony\Toml\Toml;

final class Config
{
    private static ?string $environment = null;

    private static string $projectPath = "";

    private static array $config = [];

    /**
     * This class should not be instantiated.
     */
    private function __construct()
    {
    }

    public static function load(string $projectPath, ?string $environment = null): void
    {
        self::$projectPath = $projectPath;
        $basePath = self::$projectPath . DIRECTORY_SEPARATOR . 'config';
        $envConfig = self::getConfigByPath($basePath . DIRECTORY_SEPARATOR . 'env.toml');
        self::$config = $envConfig;
        unset(self::$config['env'], self::$config['alias']);
        $environment = $environment ?? self::getEnvEnv() ?? self::getArgvEnv() ?? self::getHostEnv()
            ?? $envConfig['env'] ?? null;

        if (empty($environment)) {
            return;
        }

        self::$environment = $environment;
        $configPath = $basePath . DIRECTORY_SEPARATOR . $environment;
        if (!is_dir($configPath)) {
            $environment = null;
            foreach ($envConfig['alias'] ?? [] as $key => $value) {
                if (fnmatch($key, self::$environment)) {
                    $environment = $value;
                    break;
                }
            }
            if (empty($environment)) {
                return;
            }
            self::$environment = $environment;
            $configPath = $basePath . DIRECTORY_SEPARATOR . $environment;
        }
        if (is_dir($configPath)) {
            $config = self::getConfigByPath($configPath . DIRECTORY_SEPARATOR . '*.toml');
            self::$config = ArrayUtils::merge(self::$config, $config);
        }
    }

    /**
     * Check Composer requirements mode
     *
     * @return bool
     */
    public static function isDev(): bool
    {
        return InstalledVersions::getRootPackage()['dev'];
    }

    /**
     * Get a configuration option or null if not found
     *
     * @param string ...$path
     *            Either sections as arguments or a single argument of dot separated sections. Or even both mixed.
     * @return mixed
     */
    public static function get(string ...$path): mixed
    {
        return ArrayUtils::getNestedValue(self::$config, $path);
    }

    /**
     * Get all configuration as array.
     *
     * @return array
     */
    public static function getAll(): array
    {
        return self::$config;
    }

    /**
     * Get environment name
     *
     * @return string|null
     */
    public static function getEnvironment(): ?string
    {
        return self::$environment;
    }

    public static function getProjectPath(): string
    {
        return self::$projectPath;
    }

    private static function getConfigByPath(string $configPath): array
    {
        $config = [];
        foreach (glob($configPath) as $configFile) {
            $conf = Toml::parseFile($configFile);
            if (is_array($conf)) {
                $config = ArrayUtils::merge($config, $conf);
            }
        }
        return $config;
    }

    private static function getEnvEnv(): ?string
    {
        return getenv('APP_ENV') ?: null;
    }

    private static function getArgvEnv(): ?string
    {
        global $argv;
        global $argc;
        foreach ($argv ?? [] as $i => $item) {
            if (!str_contains($item, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $item);
            if ($key !== '--env') {
                continue;
            }
            unset($argv[$i]);
            $argv = array_values($argv);
            $argc = count($argv);
            $_SERVER['argv'] = $argv;
            $_SERVER['argc'] = $argc;
            return $value;
        }
        return null;
    }

    private static function getHostEnv(): ?string
    {
        return isset($_SERVER['HTTP_HOST']) && isset($_SERVER['SERVER_PORT'])
            ? $_SERVER['HTTP_HOST'] . ':' . $_SERVER['SERVER_PORT'] : null;
    }
}
