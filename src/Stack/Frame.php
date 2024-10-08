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
    protected ?string $function = null;
    protected ?string $className = null;
    protected ?string $namespace = null;
    protected ?string $type = null;

    /**
     * @var array<mixed>
     */
    protected array $args = [];

    protected ?string $callingFile = null;
    protected ?int $callingLine = null;
    protected ?string $originFile = null;
    protected ?int $originLine = null;


    /**
     * Generate a new trace and pull out a single frame
     * depending on the rewind range
     */
    public static function create(
        int $rewind = 0
    ): Frame {
        $data = debug_backtrace();

        if ($rewind >= count($data) - 1) {
            throw new OutOfBoundsException('Stack rewind of stack frame range');
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
     * @param array<string, mixed> $frame
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
            $this->originFile = $frame['file'];
        }

        // Line
        if (
            isset($frame['line']) &&
            is_int($frame['line'])
        ) {
            $this->originLine = $frame['line'];
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
            $this->args = (array)$frame['args'];
        }

        if (
            $this->function === '__callStatic' ||
            $this->function === '__call'
        ) {
            /** @var string|null $func */
            $func = array_shift($this->args);
            $this->function = $func;
        }
    }



    /**
     * Get detected frame type
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * Get type method invoke type
     */
    public function getInvokeType(): ?string
    {
        switch ($this->type) {
            case 'staticMethod':
                return '::';

            case 'objectMethod':
                return '->';
        }

        return null;
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
     * Is in proxy ?
     */
    public function getVeneerProxy(): ?string
    {
        $isProxy =
            //$this->function === '__callStatic' &&
            $this->className !== null &&
            (
                false !== strpos($this->className, 'veneer/src/Veneer/Binding.php') ||
                str_starts_with($this->namespace ?? '', 'DecodeLabs\\Veneer\\Binding\\')
            );

        if (!$isProxy) {
            return null;
        }

        if (
            $this->className !== null &&
            defined(($class = $this->getClass()) . '::Veneer')
        ) {
            /** @phpstan-ignore-next-line */
            return $class::Veneer;
        }

        return null;
    }




    /**
     * Get frame namespace if applicable
     */
    public function getNamespace(): ?string
    {
        return $this->namespace;
    }

    /**
     * Is there a namespace?
     */
    public function hasNamespace(): bool
    {
        return $this->namespace !== null;
    }



    /**
     * Get containing class (qualified) if applicable
     */
    public function getClass(): ?string
    {
        if ($this->className === null) {
            return null;
        }

        $output = $this->namespace !== null ?
            $this->namespace . '\\' : '';

        $output .= $this->className;
        return $output;
    }

    /**
     * Get containing class name
     */
    public function getClassName(): ?string
    {
        return $this->className;
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
            defined($class . '::Veneer')
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
     * Get function name
     */
    public function getFunctionName(): ?string
    {
        return $this->function;
    }

    /**
     * Get args array
     *
     * @return array<mixed>
     */
    public function getArgs(): array
    {
        return $this->args;
    }

    /**
     * Are there args?
     */
    public function hasArgs(): bool
    {
        return !empty($this->args);
    }

    /**
     * How many args?
     */
    public function countArgs(): int
    {
        return count($this->args);
    }

    /**
     * Generate a string representation of args
     */
    public function getArgString(): string
    {
        $output = [];

        if (!is_array($this->args)) {
            $this->args = [$this->args];
        }

        foreach ($this->args as $arg) {
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
    public function getSignature(
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
            $output .= $this->getInvokeType();
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
            $output .= $this->getArgString();
        } elseif ($argString !== null) {
            $output .= '(';

            if (!empty($this->args)) {
                $output .= count($this->args);
            }

            $output .= ')';
        }

        return $output;
    }


    /**
     * Get reflection for active frame function
     */
    public function getReflection(): ?ReflectionFunctionAbstract
    {
        if ($this->function === '{closure}') {
            return null;
        } elseif (
            $this->className !== null &&
            $this->function !== null
        ) {
            $className = $this->namespace . '\\' . $this->className;

            if (!class_exists($className)) {
                return null;
            }

            $classRef = new ReflectionClass($className);
            return $classRef->getMethod($this->function);
        } else {
            return new ReflectionFunction($this->namespace . '\\' . $this->function);
        }
    }




    /**
     * Get origin file
     */
    public function getFile(): ?string
    {
        return $this->originFile;
    }

    /**
     * Get origin line
     */
    public function getLine(): ?int
    {
        return $this->originLine;
    }

    /**
     * Get calling file
     */
    public function getCallingFile(): ?string
    {
        return $this->callingFile;
    }

    /**
     * Get calling line
     */
    public function getCallingLine(): ?int
    {
        return $this->callingLine;
    }


    /**
     * Convert to string
     */
    public function __toString(): string
    {
        return $this->getSignature() . "\n  " . Proxy::normalizePath($this->getCallingFile()) . ' : ' . $this->getCallingLine();
    }


    /**
     * Convert to a generic array
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'file' => Proxy::normalizePath($this->getFile()),
            'line' => $this->getLine(),
            'function' => $this->function,
            'class' => $this->className,
            'namespace' => $this->namespace,
            'type' => $this->type,
            'args' => $this->args
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
            'file' => Proxy::normalizePath($this->getFile()),
            'line' => $this->getLine(),
            'signature' => $this->getSignature()
        ];
    }
}
