<?php
/**
 * This file is part of the Glitch Support package
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);
namespace DecodeLabs\Glitch\Path;

/**
 * This class represents a static, global repository of path aliases
 */
final class Normalizer
{
    protected static $aliases = [];

    /**
     * Protected constructor inhibits instantiation
     */
    protected function __construct()
    {
    }

    /**
     * Register path replacement alias
     */
    public static function registerAlias(string $name, string $path): void
    {
        $path = rtrim($path, '/').'/';
        self::$aliases[$name] = $path;

        try {
            if (($realPath = realpath($path)) && $realPath.'/' !== $path) {
                self::$aliases[$name.'*'] = $realPath.'/';
            }
        } catch (\Throwable $e) {
        }

        uasort(self::$aliases, function ($a, $b) {
            return strlen($b) - strlen($a);
        });
    }

    /**
     * Register list of path replacement aliases
     */
    public static function registerAliases(array $aliases): void
    {
        foreach ($aliases as $name => $path) {
            $path = rtrim($path, '/').'/';
            self::$aliases[$name] = $path;

            try {
                if (($realPath = realpath($path)) && $realPath.'/' !== $path) {
                    self::$aliases[$name.'*'] = $realPath.'/';
                }
            } catch (\Throwable $e) {
            }
        }

        uasort(self::$aliases, function ($a, $b) {
            return strlen($b) - strlen($a);
        });
    }

    /**
     * Inspect list of registered path aliases
     */
    public static function getAliases(): array
    {
        return self::$aliases;
    }

    /**
     * Lookup and replace path prefix
     */
    public static function normalize(?string $path): ?string
    {
        if ($path === null) {
            return null;
        }

        $path = str_replace('\\', '/', $path);
        $testPath = rtrim($path, '/').'/';

        foreach (self::$aliases as $name => $test) {
            $len = strlen($test);

            if ($testPath === $test) {
                return rtrim($name, '*').'://'.ltrim($path, '/');
            } elseif (substr($testPath, 0, $len) == $test) {
                return rtrim($name, '*').'://'.ltrim(substr($path, $len), '/');
            }
        }

        return $path;
    }
}
