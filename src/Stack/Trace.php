<?php

/**
 * @package GlitchSupport
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Glitch\Stack;

use ArrayAccess;
use ArrayIterator;
use BadMethodCallException;
use Countable;
use DecodeLabs\Glitch\Dumpable;
use DecodeLabs\Glitch\Proxy;
use IteratorAggregate;
use JsonSerializable;
use OutOfBoundsException;
use Throwable;
use Traversable;
use UnexpectedValueException;

/**
 * Represents a normalized stack trace
 *
 * @implements IteratorAggregate<int, Frame>
 * @implements ArrayAccess<int, Frame>
 */
class Trace implements
    IteratorAggregate,
    ArrayAccess,
    JsonSerializable,
    Countable,
    Dumpable
{
    /**
     * @var array<int, Frame>
     */
    protected array $frames = [];

    /**
     * Extract trace from exception and build
     */
    public static function fromException(
        Throwable $e,
        int $rewind = 0
    ): self {
        if ($e instanceof PreparedTraceException) {
            return $e->getStackTrace();
        }

        $output = self::fromArray($e->getTrace(), $rewind);

        array_unshift($output->frames, new Frame([
            'fromFile' => $e->getFile(),
            'fromLine' => $e->getLine(),
            'function' => '__construct',
            'class' => get_class($e),
            'type' => '->',
            'args' => [
                $e->getMessage(),
                $e->getCode(),
                $e->getPrevious()
            ]
        ]));

        return $output;
    }

    /**
     * Generate a backtrace and build
     */
    public static function create(
        int $rewind = 0
    ): self {
        return self::fromArray(debug_backtrace(), $rewind);
    }

    /**
     * Take a trace array and convert to objects
     *
     * @param array<array<mixed>> $trace
     */
    public static function fromArray(
        array $trace,
        int $rewind = 0
    ): self {
        $last = null;

        if ($rewind) {
            if ($rewind > count($trace) - 1) {
                throw new OutOfBoundsException(
                    'Stack rewind out of stack frame range'
                );
            }

            while ($rewind >= 0) {
                $rewind--;
                $last = array_shift($trace);
            }
        }

        if (!$last) {
            $last = $trace[0] ?? null;
        }

        $last['fromFile'] = $last['file'] ?? null;
        $last['fromLine'] = $last['line'] ?? null;
        $output = [];

        foreach ($trace as $frame) {
            // Skip Venetian proxy frames
            /** @var string $file */
            $file = $frame['file'] ?? '';

            if (str_ends_with(
                $file,
                'Veneer/ProxyTrait.php'
            )) {
                continue;
            }

            $frame['fromFile'] = $frame['file'] ?? null;
            $frame['fromLine'] = $frame['line'] ?? null;
            $frame['file'] = $last['fromFile'];
            $frame['line'] = $last['fromLine'];

            $output[] = new Frame($frame);
            $last = $frame;
        }

        return new self($output);
    }


    /**
     * Check list of frames
     *
     * @param array<Frame> $frames
     */
    public function __construct(
        array $frames
    ) {
        foreach ($frames as $frame) {
            if (!$frame instanceof Frame) {
                throw new UnexpectedValueException(
                    'Trace frame is not an instance of DecodeLabs\\Glitch\\Stack\\Frame'
                );
            }

            $this->frames[] = $frame;
        }
    }



    /**
     * Get the frame list as an array
     *
     * @return array<Frame>
     */
    public function getFrames(): array
    {
        return $this->frames;
    }

    /**
     * Get first frame
     */
    public function getFirstFrame(): ?Frame
    {
        return $this->frames[0] ?? null;
    }

    /**
     * Get frame by offset
     */
    public function getFrame(
        int $offset
    ): ?Frame {
        return $this->frames[$offset] ?? null;
    }

    /**
     * Shift the top frame from the stack
     */
    public function shift(): ?Frame
    {
        return array_shift($this->frames);
    }


    /**
     * Get file from first frame
     */
    public function getFile(): ?string
    {
        if (!isset($this->frames[0])) {
            return null;
        }

        return $this->frames[0]->getFile();
    }

    /**
     * Get line from first frame
     */
    public function getLine(): ?int
    {
        if (!isset($this->frames[0])) {
            return null;
        }

        return $this->frames[0]->getLine();
    }



    /**
     * Create iterator
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->frames);
    }


    /**
     * Export to generic array
     *
     * @return array<array<mixed>>
     */
    public function toArray(): array
    {
        return array_map(function ($frame) {
            return $frame->toArray();
        }, $this->frames);
    }

    /**
     * Convert to json serializable state
     *
     * @return array<array<string, mixed>>
     */
    public function jsonSerialize(): array
    {
        return array_map(function ($frame) {
            return $frame->jsonSerialize();
        }, $this->frames);
    }

    /**
     * Count frames
     */
    public function count(): int
    {
        return count($this->frames);
    }


    /**
     * Convert to string
     */
    public function __toString(): string
    {
        $output = '';
        $count = $this->count();
        $pad = strlen((string)$count);

        foreach ($this->frames as $frame) {
            $frameString = $frame->getSignature() . "\n" .
                str_repeat(' ', $pad + 1) .
                Proxy::normalizePath($frame->getCallingFile()) . ' : ' . $frame->getCallingLine();

            $output .= str_pad((string)$count--, $pad, ' ', \STR_PAD_LEFT) . ': ' . $frameString . "\n";
        }

        return $output;
    }


    /**
     * Set offset
     */
    public function offsetSet(
        mixed $offset,
        mixed $value
    ): void {
        throw new BadMethodCallException('Stack traces cannot be changed after instantiation');
    }

    /**
     * Get by index
     *
     * @param int $offset
     */
    public function offsetGet(
        mixed $offset
    ): ?Frame {
        return $this->frames[$offset] ?? null;
    }

    /**
     * Has offset?
     *
     * @param int $offset
     */
    public function offsetExists(
        mixed $offset
    ): bool {
        return isset($this->frames[$offset]);
    }

    /**
     * Remove offset
     *
     * @param int $offset
     */
    public function offsetUnset(
        mixed $offset
    ): void {
        throw new BadMethodCallException('Stack traces cannot be changed after instantiation');
    }





    /**
     * Debug info
     *
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        $output = [];
        $frames = $this->getFrames();
        $count = count($frames);

        foreach ($frames as $i => $frame) {
            $output[($count - $i) . ': ' . $frame->getSignature(true)] = [
                'file' => Proxy::normalizePath($frame->getCallingFile()) . ' : ' . $frame->getCallingLine()
            ];
        }

        return $output;
    }


    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        yield 'type' => 'stack';
        yield 'length' => count($this->frames);
        yield 'stackTrace' => $this;
    }
}
