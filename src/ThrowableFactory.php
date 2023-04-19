<?php

namespace TypesafePayload\TypesafePayload;

use Throwable;

interface ThrowableFactory {

    /**
     * Allows creating a custom throwable that the payload walker throws when it encounters a bad payload
     *
     * @param string $expectedType
     * @param string $actualType
     * @param string|int|null $payloadVariable
     * @param string|int ...$payloadVariableSubPath
     *
     * @see BadPayloadException::formatVariablePath() provides a way to convert the variable path to a human-readable string
     *
     * @return Throwable
     */
    function createThrowable (
        string $expectedType,
        string $actualType,
        string|int|null $payloadVariable = null,
        string|int ...$payloadVariableSubPath
    ) : Throwable;

}