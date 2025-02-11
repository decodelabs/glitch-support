<?php

/**
 * @package GlitchSupport
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Glitch;

interface Dumpable
{
    /**
     * @return iterable<string,mixed>
     */
    public function glitchDump(): iterable;
}
