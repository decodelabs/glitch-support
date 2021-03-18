<?php

/**
 * @package GlitchSupport
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Glitch\Stack;

interface PreparedTraceException
{
    public function getStackFrame(): Frame;
    public function getStackTrace(): Trace;
}
