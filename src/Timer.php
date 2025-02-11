<?php

/**
 * @package Glitch
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Glitch;

class Timer implements Dumpable
{
    public private(set) float $start;
    public private(set) ?float $end = null;

    public float $time {
        get => ($this->end ?? microtime(true)) - $this->start;
    }

    /**
     * Init with start time
     */
    public function __construct(
        ?float $start = null
    ) {
        if ($start === null) {
            $start = microtime(true);
        }

        $this->start = $start;
    }


    public function isRunning(): bool {
        return $this->end === null;
    }


    /**
     * Set end time
     *
     * @return $this
     */
    public function stop(
        ?float $end = null
    ): static {
        if ($end === null) {
            $end = microtime(true);
        }

        $this->end = $end;
        return $this;
    }


    /**
     * Dump for glitch
     */
    public function glitchDump(): iterable
    {
        yield 'text' => number_format(
            $this->time * 1000,
            2
        ) . ' ms';
    }
}
