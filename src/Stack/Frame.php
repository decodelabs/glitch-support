<?php

/**
 * @package GlitchSupport
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Glitch\Stack;

use DecodeLabs\Glitch\Proxy;
use JsonSerializable;
use OutOfBoundsException;
use ReflectionClass;
use ReflectionFunction;
use ReflectionFunctionAbstract;

/**
 * Represents a single entry in a stack trace
 */
class Frame implements JsonSerializable
{
    public protected(set) ?string $function = null;
    public protected(set) ?string $className = null;
    public protected(set) ?string $namespace = null;

    public ?string $class {
        get {
            if ($this->className === null) {
                return null;
            }

            $output = $this->namespace !== null ?
                $this->namespace . '\\' : '';

            $output .= $this->className;
            return $output;
        }
    }

    public protected(set) ?string $type = null;

    public ?string $invokeType {
        get => match ($this->type) {
            'staticMethod' => '::',
            'objectMethod' => '->',
            default => null
        };
    }

    /**
     * @var array<string|int,mixed>
     */
    public protected(set) array $arguments = [];

    public string $signature {
        get => $this->buildSignature();
    }

    public protected(set) ?string $callingFile = null;
    public protected(set) ?int $callingLine = null;
    public protected(set) ?string $file = null;
    public protected(set) ?int $line = null;

    public ?ReflectionFunctionAbstract $reflection {
        get {
            if ($this->function === '{closure}') {
                return null;
            }

            if (
                $this->className !== null &&
                $this->function !== null
            ) {
                $className = $this->namespace . '\\' . $this->className;

                if (!class_exists($className)) {
                    return null;
                }

                $classRef = new ReflectionClass($className);

                if(!$classRef->hasMethod($this->function)) {
                    return null;
                }

                return $classRef->getMethod($this->function);
            }

            return new ReflectionFunction($this->namespace . '\\' . $this->function);
        }
    }


    /**
     * Generate a new trace and pull out a single frame
     * depending on the rewind range
     */
    public static function create(
        int $rewind = 0
    ): Frame {
        $data = debug_backtrace();

        if ($rewind >= count($data) - 1) {
            throw new OutOfBoundsException('Stack rewind out of stack frame range');
        }

        if ($rewind) {
            $data = array_slice($data, $rewind);
        }

        $last = array_shift($data);
        $output = array_shift($data);

        $output['fromFile'] = $output['file'] ?? null;
        $output['fromLine'] = $output['line'] ?? null;
        $output['file'] = $last['file'] ?? null;
        $output['line'] = $last['line'] ?? null;

        return new self($output);
    }


    /**
     * Build the frame object from a stack trace frame array
     *
     * @param array<string,mixed> $frame
     */
    public function __construct(
        array $frame
    ) {
        // Calling file
        if (
            isset($frame['fromFile']) &&
            is_string($frame['fromFile'])
        ) {
            $this->callingFile = $frame['fromFile'];
        }

        // Calling line
        if (
            isset($frame['fromLine']) &&
            is_int($frame['fromLine'])
        ) {
            $this->callingLine = $frame['fromLine'];
        }

        // File
        if (
            isset($frame['file']) &&
            is_string($frame['file'])
        ) {
            $this->file = $frame['file'];
        }

        // Line
        if (
            isset($frame['line']) &&
            is_int($frame['line'])
        ) {
            $this->line = $frame['line'];
        }

        // Function
        if (
            isset($frame['function']) &&
            is_string($frame['function'])
        ) {
            $this->function = $frame['function'];
        }

        // Class
        if (
            isset($frame['class']) &&
            is_string($frame['class'])
        ) {
            $parts = explode('\\', $frame['class']);
            $this->className = array_pop($parts);
        } elseif ($this->function !== null) {
            $parts = explode('\\', $this->function);
            $this->function = array_pop($parts);
        }

        // Namespace
        if (!empty($parts)) {
            $this->namespace = implode('\\', $parts);
        }

        // Type
        if (isset($frame['type'])) {
            switch ($frame['type']) {
                case '::':
                    $this->type = 'staticMethod';
                    break;

                case '->':
                    $this->type = 'objectMethod';
                    break;
            }
        } elseif ($this->namespace !== null) {
            $this->type = 'namespaceFunction';
        } elseif ($this->function) {
            $this->type = 'globalFunction';
        }

        // Args
        if (isset($frame['args'])) {
            $this->arguments = (array)$frame['args'];
        }

        if (
            $this->function === '__callStatic' ||
            $this->function === '__call'
        ) {
            /** @var string|null $func */
            $func = array_shift($this->arguments);
            $this->function = $func;
        }
    }


    /**
     * Is type static method?
     */
    public function isStaticMethod(): bool
    {
        return $this->type === 'staticMethod';
    }

    /**
     * Is type object method?
     */
    public function isObjectMethod(): bool
    {
        return $this->type === 'objectMethod';
    }

    /**
     * Is type namespace function?
     */
    public function isNamespaceFunction(): bool
    {
        return $this->type === 'namespaceFunction';
    }

    /**
     * Is type global function?
     */
    public function isGlobalFunction(): bool
    {
        return $this->type === 'globalFunction';
    }


    /**
     * Is there a namespace?
     */
    public function hasNamespace(): bool
    {
        return $this->namespace !== null;
    }

    /**
     * Is there a class?
     */
    public function hasClass(): bool
    {
        return $this->className !== null;
    }

    /**
     * Normalize a classname
     */
    public static function normalizeClassName(
        string $class,
        bool $alias = true
    ): string {
        if (
            $alias &&
            (
                false !== strpos($class, 'veneer/src/Veneer/Binding.php') ||
                str_starts_with($class, 'DecodeLabs\\Veneer\\Binding\\')
            ) &&
            defined($class . '::Veneer') &&
            is_string($class::Veneer)
        ) {
            return '~' . $class::Veneer;
        }

        $name = [];
        $parts = explode(':', $class);


        while (!empty($parts)) {
            $part = trim(array_shift($parts));

            if (preg_match('/^class@anonymous(.+)(\(([0-9]+)\))/', $part, $matches)) {
                $name[] = Proxy::normalizePath(trim($matches[1])) . ' : ' . ($matches[3]);
            } elseif (preg_match('/^class@anonymous(.+)(0x[0-9a-f]+)/', $part, $matches)) {
                $partName = Proxy::normalizePath(trim($matches[1]));

                if ($partName === trim($matches[1])) {
                    $partName = basename($partName);
                }

                $name[] = '@anonymous : ' . $partName;
            } elseif (preg_match('/^eval\(\)\'d/', $part)) {
                $name = ['eval[ ' . implode(' : ', $name) . ' ]'];
            } else {
                $name[] = $part;
            }
        }

        return implode(' : ', $name);
    }


    /**
     * Are there args?
     */
    public function hasArgs(): bool
    {
        return !empty($this->arguments);
    }

    /**
     * How many args?
     */
    public function countArgs(): int
    {
        return count($this->arguments);
    }

    /**
     * Generate a string representation of args
     */
    protected function buildArgumentString(): string
    {
        $output = [];

        foreach ($this->arguments as $arg) {
            if (is_string($arg)) {
                if (strlen($arg) > 16) {
                    $arg = substr($arg, 0, 16) . '...';
                }

                $arg = '\'' . $arg . '\'';
            } elseif (is_array($arg)) {
                $arg = '[' . count($arg) . ']';
            } elseif (is_object($arg)) {
                $arg = self::normalizeClassName(get_class($arg));
            } elseif (is_bool($arg)) {
                $arg = $arg ? 'true' : 'false';
            } elseif (is_null($arg)) {
                $arg = 'null';
            }

            $output[] = $arg;
        }

        return '(' . implode(', ', $output) . ')';
    }


    /**
     * Generate a full frame signature
     */
    public function buildSignature(
        ?bool $argString = false,
        bool $namespace = true
    ): string {
        $output = '';

        if ($namespace && $this->namespace !== null) {
            $output = $this->namespace . '\\';
        }

        if ($this->className !== null) {
            $className = self::normalizeClassName($this->className);

            if (substr($className, 0, 1) == '~') {
                $output = '';
            }

            $output .= $className;
        }

        if ($this->type) {
            $output .= $this->invokeType;
        }

        if (
            $this->function !== null &&
            false !== strpos($this->function, '{closure}')
        ) {
            $output .= '{closure}';
        } else {
            $output .= $this->function;
        }

        if ($argString) {
            $output .= $this->buildArgumentString();
        } elseif ($argString !== null) {
            $output .= '(';

            if (!empty($this->arguments)) {
                $output .= count($this->arguments);
            }

            $output .= ')';
        }

        return $output;
    }


    /**
     * Convert to string
     */
    public function __toString(): string
    {
        return
            $this->buildSignature() . "\n  " .
            Proxy::normalizePath($this->callingFile) . ' : ' . $this->callingLine;
    }


    /**
     * Convert to a generic array
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'file' => Proxy::normalizePath($this->file),
            'line' => $this->line,
            'function' => $this->function,
            'class' => $this->className,
            'namespace' => $this->namespace,
            'type' => $this->type,
            'args' => $this->arguments
        ];
    }

    /**
     * Convert to json serializable state
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'file' => Proxy::normalizePath($this->file),
            'line' => $this->line,
            'signature' => $this->buildSignature()
        ];
    }
}
