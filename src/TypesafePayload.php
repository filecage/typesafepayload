<?php

namespace TypesafePayload\TypesafePayload;

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
        if (!is_array($this->payloadData) && !($this->payloadData instanceof \ArrayAccess)) {
            throw $this->createThrowable('array');
        }

        $clone = clone $this;
        $clone->payloadData = $this->payloadData[$index] ?? new EmptyPayload();
        $clone->variablePath[] = $index;

        return $clone;
    }

    /**
     * @return \Generator<TypesafePayload>
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
        if ($this->payloadData instanceof EmptyPayload) {
            $clone = clone $this;
            $clone->payloadData = $payloadData;

            return $clone;
        }

        return $this;
    }

    /**
     * @throws Throwable
     */
    function asString () : string {
        if (!is_string($this->payloadData)) {
            throw $this->createThrowable('string');
        }

        return $this->payloadData;
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
     * @return bool[]
     * @throws Throwable
     */
    function asBooleanList () : array {
        return array_map(fn(self $payload) => $payload->asBoolean(), iterator_to_array($this->iterate()));
    }

    private function createThrowable (string $expectedType) : Throwable {
        $actualType = $this->payloadData instanceof EmptyPayload ? '(empty)' : gettype($this->payloadData);
        if ($this->throwableFactory) {
            return $this->throwableFactory->createThrowable($expectedType, $actualType, ...$this->variablePath);
        }

        return new BadPayloadException($expectedType, $actualType, ...$this->variablePath);
    }


}