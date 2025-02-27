<?php

/**
 * @package GlitchSupport
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Glitch;

use DecodeLabs\Glitch;
use DecodeLabs\Glitch\Stack\Trace;

use Throwable;

/**
 * This class represents a static, global handle on Glitch
 */
final class Proxy
{
    /**
     * Protected constructor inhibits instantiation
     */
    private function __construct()
    {
    }


    /**
     * Lookup and replace path prefix
     */
    public static function normalizePath(
        ?string $path
    ): ?string {
        if ($path === null) {
            return null;
        }

        if (!class_exists('DecodeLabs\\Glitch')) {
            return $path;
        }

        // @phpstan-ignore-next-line
        return Glitch::normalizePath($path);
    }



    /**
     * Log exception
     */
    public static function logException(
        Throwable $exception
    ): void {
        if (!class_exists('DecodeLabs\\Glitch')) {
            return;
        }

        Glitch::logException($exception);
    }



    /**
     * Get current run mode
     */
    public static function getRunMode(): string
    {
        if (!class_exists('DecodeLabs\\Glitch')) {
            return 'production';
        }

        // @phpstan-ignore-next-line
        return Glitch::getRunMode();
    }


    /**
     * Is Glitch in development mode?
     */
    public static function isDevelopment(): bool
    {
        if (!class_exists('DecodeLabs\\Glitch')) {
            return false;
        }

        // @phpstan-ignore-next-line
        return Glitch::isDevelopment();
    }

    /**
     * Is Glitch in testing mode?
     */
    public static function isTesting(): bool
    {
        if (!class_exists('DecodeLabs\\Glitch')) {
            return false;
        }

        // @phpstan-ignore-next-line
        return Glitch::isTesting();
    }

    /**
     * Is Glitch in production mode?
     */
    public static function isProduction(): bool
    {
        if (!class_exists('DecodeLabs\\Glitch')) {
            return true;
        }

        // @phpstan-ignore-next-line
        return Glitch::isProduction();
    }


    /**
     * Shortcut to incomplete context method
     */
    public static function incomplete(
        mixed $data = null,
        int $rewind = 0
    ): never {
        throw new IncompleteException(
            Trace::create($rewind),
            $data
        );
    }


    /**
     * Create a new stack trace
     */
    public static function stackTrace(
        int $rewind = 0
    ): Trace {
        return Trace::create($rewind);
    }
}
