<?php
/**
 * This file is part of the Glitch Support package
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);
namespace DecodeLabs\Glitch\Path;

/**
 * This class represents a static, global handle on Glitch
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
     * Lookup and replace path prefix
     */
    public static function normalize(?string $path): ?string
    {
        if ($path === null) {
            return null;
        }

        if (!class_exists('DecodeLabs\\Glitch')) {
            return $path;
        }

        return \DecodeLabs\Glitch::normalizePath($path);
    }
}
