<?php

/**
 * @package GlitchSupport
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Glitch;

use DecodeLabs\Glitch\Proxy as Glitch;
use DecodeLabs\Glitch\Stack\Frame;
use DecodeLabs\Glitch\Stack\Trace;

use Exception;

class IncompleteException extends Exception
{
    protected $stackTrace;
    protected $data;

    /**
     * Init with frame info
     */
    public function __construct(Trace $trace, $data = null)
    {
        if ($frame = $trace[1]) {
            $message = $frame->getSignature() . ' has not been implemented yet';
            $this->file = $frame->getFile();
            $this->line = $frame->getLine();
        } elseif ($frame = $trace[0]) {
            $message = Glitch::normalizePath($frame->getFile()) . ' has not been implemented yet';
            $this->file = $frame->getFile();
            $this->line = $frame->getLine();
        } else {
            $message = 'Feature has not been implemented yet';
        }

        $this->stackTrace = $trace;
        $this->data = $data;
        parent::__construct($message, 501);
    }

    /**
     * Get generated stack trace
     */
    public function getStackTrace(): Trace
    {
        return $this->stackTrace;
    }

    /**
     * Get data
     */
    public function getData()
    {
        return $this->data;
    }
}
