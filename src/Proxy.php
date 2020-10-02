<?php
/**
 * This file is part of the Glitch Support package
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);
namespace DecodeLabs\Glitch;

use Throwable;

/**
 * This class represents a static, global handle on Glitch
 */
final class Proxy
{
    /**
     * Protected constructor inhibits instantiation
     */
    protected function __construct()
    {
    }


    /**
     * Lookup and replace path prefix
     */
    public static function normalizePath(?string $path): ?string
    {
        if ($path === null) {
            return null;
        }

        if (!class_exists('DecodeLabs\\Glitch')) {
            return $path;
        }

        return \DecodeLabs\Glitch::normalizePath($path);
    }



    /**
     * Log exception
     */
    public static function logException(Throwable $exception): void
    {
        if (!class_exists('DecodeLabs\\Glitch')) {
            return;
        }

        \DecodeLabs\Glitch::logException($exception);
    }




    /**
     * Is Glitch in development mode?
     */
    public static function isDevelopment(): bool
    {
        if (!class_exists('DecodeLabs\\Glitch')) {
            return false;
        }

        return \DecodeLabs\Glitch::isDevelopment();
    }

    /**
     * Is Glitch in testing mode?
     */
    public static function isTesting(): bool
    {
        if (!class_exists('DecodeLabs\\Glitch')) {
            return false;
        }

        return \DecodeLabs\Glitch::isTesting();
    }

    /**
     * Is Glitch in production mode?
     */
    public static function isProduction(): bool
    {
        if (!class_exists('DecodeLabs\\Glitch')) {
            return true;
        }

        return \DecodeLabs\Glitch::isProduction();
    }
}
