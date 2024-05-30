<?php

namespace TypesafePayload\TypesafePayload;

use ArrayAccess;
use Generator;
use Stringable;
use Throwable;

final class TypesafePayload {
    /** @var (string|int)[] */
    private array $variablePath = [];

    function __construct(private mixed $payloadData, private readonly ?ThrowableFactory $throwableFactory = null) {}

    /**
     * @throws Throwable
     */
    function property (string $key) : self {
        if (!is_object($this->payloadData) || $this->payloadData instanceof EmptyPayload) {
            throw $this->createThrowable('object');
        }

        $clone = clone $this;
        $clone->payloadData = $this->payloadData->$key ?? new EmptyPayload();
        $clone->variablePath[] = $key;

        return $clone;
    }

    /**
     * @throws Throwable
     */
    function index (int $index) : self {
        if (!is_array($this->payloadData) && !($this->payloadData instanceof ArrayAccess)) {
            throw $this->createThrowable('array');
        }

        $clone = clone $this;
        $clone->payloadData = $this->payloadData[$index] ?? new EmptyPayload();
        $clone->variablePath[] = $index;

        return $clone;
    }

    /**
     * @return Generator<TypesafePayload>
     * @throws Throwable
     */
    function iterate () : iterable {
        if (!is_iterable($this->payloadData)) {
            throw $this->createThrowable('iterable');
        }

        /**
         * @psalm-suppress MixedAssignment
         */
        foreach ($this->payloadData as $index => $payloadData) {
            $item = clone $this;
            $item->payloadData = $payloadData;
            $item->variablePath[] = is_int($index) || is_string($index) ? $index : gettype($index);

            yield $index => $item;
        }
    }

    function fillEmpty (mixed $payloadData) : self {
        if ($this->isEmpty()) {
            $clone = clone $this;
            $clone->payloadData = $payloadData instanceof self ? $payloadData->payloadData : $payloadData;

            return $clone;
        }

        return $this;
    }

    function isEmpty () : bool {
        return $this->payloadData instanceof EmptyPayload;
    }

    /**
     * @throws Throwable
     */
    function asString () : string {
        if (!is_string($this->payloadData) && !$this->payloadData instanceof Stringable) {
            throw $this->createThrowable('string');
        }

        return (string) $this->payloadData;
    }

    /**
     * @throws Throwable
     */
    function asStringOrNull () : ?string {
        if ($this->isEmpty() || $this->payloadData === null) {
            return null;
        }

        return $this->asString();
    }

    /**
     * @return string[]
     * @throws Throwable
     */
    function asStringList () : array {
        return array_map(fn(self $payload) => $payload->asString(), iterator_to_array($this->iterate()));
    }

    /**
     * @throws Throwable
     */
    function asInteger () : int {
        if (!is_int($this->payloadData)) {
            throw $this->createThrowable('integer');
        }

        return $this->payloadData;
    }

    /**
     * @throws Throwable
     */
    function asIntegerOrNull () : ?int {
        if ($this->isEmpty() || $this->payloadData === null) {
            return null;
        }

        return $this->asInteger();
    }

    /**
     * @return int[]
     * @throws Throwable
     */
    function asIntegerList () : array {
        return array_map(fn(self $payload) => $payload->asInteger(), iterator_to_array($this->iterate()));
    }

    /**
     * @throws Throwable
     */
    function asBoolean () : bool {
        if (!is_bool($this->payloadData)) {
            throw $this->createThrowable('bool');
        }

        return $this->payloadData;
    }

    /**
     * @throws Throwable
     */
    function asBooleanOrNull () : ?bool {
        if ($this->isEmpty() || $this->payloadData === null) {
            return null;
        }

        return $this->asBoolean();
    }

    /**
     * @return bool[]
     * @throws Throwable
     */
    function asBooleanList () : array {
        return array_map(fn(self $payload) => $payload->asBoolean(), iterator_to_array($this->iterate()));
    }

    /**
     * @template T of object
     * @param class-string<T> $classOrInterfaceName
     *
     * @return T
     * @throws Throwable
     */
    function asInstanceOf (string $classOrInterfaceName) : object {
        if (!is_object($this->payloadData) || !is_a($this->payloadData, $classOrInterfaceName)) {
            throw $this->createThrowable('instanceof ' . $classOrInterfaceName);
        }

        return $this->payloadData;
    }

    /**
     * @template T of object
     * @param class-string<T> $classOrInterfaceName
     *
     * @return T|null
     * @throws Throwable
     */
    function asInstanceOfOrNull (string $classOrInterfaceName) : ?object {
        if ($this->isEmpty() || $this->payloadData === null) {
            return null;
        }

        return $this->asInstanceOf($classOrInterfaceName);
    }

    /**
     * Returns the actual data currently held by the payload, if not empty
     *
     * @return mixed
     * @throws Throwable
     */
    function asUnsafeMixed () : mixed {
        if ($this->payloadData instanceof EmptyPayload) {
            throw $this->createThrowable('(non-empty)');
        }

        return $this->payloadData;
    }

    private function createThrowable (string $expectedType) : Throwable {
        $actualType = is_object($this->payloadData) ? ($this->payloadData instanceof EmptyPayload ? '(empty)' : 'instanceof ' . get_class($this->payloadData)) : gettype($this->payloadData);
        if ($this->throwableFactory) {
            return $this->throwableFactory->createThrowable($expectedType, $actualType, ...$this->variablePath);
        }

        return new BadPayloadException($expectedType, $actualType, ...$this->variablePath);
    }


}