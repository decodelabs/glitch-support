<?php

/**
 * @package GlitchSupport
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Glitch;

use DecodeLabs\Glitch\Proxy as Glitch;
use DecodeLabs\Glitch\Stack\PreparedTraceException;
use DecodeLabs\Glitch\Stack\Frame;
use DecodeLabs\Glitch\Stack\Trace;

use Exception;

class IncompleteException extends Exception implements PreparedTraceException
{
    public private(set) Trace $stackTrace;

    public ?Frame $stackFrame {
        get => $this->stackTrace->getFirstFrame();
    }

    public private(set) mixed $data;

    /**
     * Init with frame info
     */
    public function __construct(
        Trace $trace,
        mixed $data = null
    ) {
        if ($frame = $trace[1]) {
            $message = $frame->buildSignature() . ' has not been implemented yet';
            $this->file = (string)$frame->file;
            $this->line = (int)$frame->line;
        } elseif ($frame = $trace[0]) {
            $message = Glitch::normalizePath($frame->file) . ' has not been implemented yet';
            $this->file = (string)$frame->file;
            $this->line = (int)$frame->line;
        } else {
            $message = 'Feature has not been implemented yet';
        }

        $this->stackTrace = $trace;
        $this->data = $data;

        parent::__construct($message, 501);
    }
}
