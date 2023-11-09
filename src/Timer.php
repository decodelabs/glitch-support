<?php

/**
 * @package Glitch
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Glitch;

class Timer implements Dumpable
{
    protected float $start;
    protected ?float $stop = null;

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

    /**
     * Get start time
     */
    public function getStart(): float
    {
        return $this->start;
    }

    /**
     * Set stop time
     *
     * @return $this
     */
    public function stop(
        ?float $stop = null
    ): static {
        if ($stop === null) {
            $stop = microtime(true);
        }

        $this->stop = $stop;
        return $this;
    }

    /**
     * Get stop
     */
    public function getStop(): ?float
    {
        return $this->stop;
    }

    /**
     * Get elapsed time
     */
    public function getTime(): float
    {
        return ($this->stop ?? microtime(true)) - $this->start;
    }

    /**
     * Dump for glitch
     */
    public function glitchDump(): iterable
    {
        yield 'text' => number_format(
            $this->getTime() * 1000,
            2
        ) . ' ms';
    }
}
