<?php

/**
 * @package GlitchSupport
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Glitch\Stack;

interface PreparedTraceException
{
    public Trace $stackTrace { get; }
    public ?Frame $stackFrame { get; }
}
