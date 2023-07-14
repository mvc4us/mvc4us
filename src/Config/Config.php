<?php

declare(strict_types=1);

namespace Mvc4us\Config;

use Mvc4us\Utils\ArrayUtils;
use Yosymfony\Toml\Toml;

final class Config
{
    private static ?string $environment = null;

    private static string $projectPath = "";

    private static array $config = [
        'debug' => false
    ];

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
        self::$config = ArrayUtils::merge(self::$config, $envConfig);
        unset(self::$config['env'], self::$config['alias']);
        $environment = $environment ?? $_ENV['MVC4US_ENV'] ?? $_SERVER['MVC4US_ENV'] ?? self::getArgvEnv()
            ?? self::getHostEnv() ?? $envConfig['env'] ?? null;

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
     * Check if in debug mode
     *
     * @return bool
     */
    public static function isDebug(): bool
    {
        return self::$config['debug'] ?? false;
    }

    /**
     * Get all configuration options of section
     *
     * @param string $section
     *            config section name
     * @return array
     */
    public static function getAll(string $section): array
    {
        if (isset(self::$config[$section]) && is_array(self::$config[$section])) {
            return self::$config[$section];
        }
        // throw new InvalidConfigException(sprintf('Missing configuration section "%s".', $section));
        return [];
    }

    /**
     * Get a configuration option or null if option not found
     *
     * @param string $section
     *            config section name
     * @param string $option
     *            config option in section
     * @return mixed|null
     */
    public static function get(string $section, string $option): mixed
    {
        $config = self::getAll($section);
        if (isset($config[$option])) {
            return $config[$option];
        }
        // throw new InvalidConfigException(sprintf('Missing configuration option "%s" in section "%s".', $option, $section));
        return null;
    }

    /**
     * Use getEnvironment() instead
     *
     * @return string|null
     * @deprecated
     */
    public static function environment(): ?string
    {
        return self::$environment;
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

    private static function setConfigByPath(string $configPath, string $environment): void
    {
        $config = [];
        foreach (glob($configPath . DIRECTORY_SEPARATOR . $environment . '/*.toml') as $configFile) {
            $conf = Toml::parseFile($configFile);
            if (is_array($conf)) {
                $config = ArrayUtils::merge($config, $conf);
            }
            // throw new InvalidConfigException(sprintf('Error loading config file "%s".', $configFile));
        }

        self::$config = ArrayUtils::merge(self::$config, $config);
        self::$environment = $environment;
    }

    private static function getArgvEnv(): ?string
    {
        foreach ($_SERVER['argv'] ?? [] as $i => $item) {
            if (!str_contains($item, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $item);
            if ($key === '--env') {
                unset($_SERVER['argv'][$i]);
                $_SERVER['argc'] -= 1;
                return $value;
            }
        }
        return null;
    }

    private static function getHostEnv(): ?string
    {
        return isset($_SERVER['HTTP_HOST']) && isset($_SERVER['SERVER_PORT'])
            ? $_SERVER['HTTP_HOST'] . ':' . $_SERVER['SERVER_PORT'] : null;
    }
}
