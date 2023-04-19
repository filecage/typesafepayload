<?php

namespace TypesafePayload\TypesafePayload;

class BadPayloadException extends \Exception {

    static function formatVariablePath (string|int $variableName, string|int ...$variablePath) : string {
        $buffer = '$' . (is_int($variableName) ? "[{$variableName}]" : $variableName);
        foreach ($variablePath as $part) {
            if (is_int($part)) {
                $buffer .= "[{$part}]";
            } else {
                $buffer .= '->$' . $part;
            }
        }

        return $buffer;
    }

    function __construct (string $expectedType, string $actualType, string|int|null $payloadVariable = null, string|int ...$payloadVariableSubPath) {
        if ($payloadVariable === null) {
            parent::__construct("Unexpected payload type, expected `{$expectedType}` but got `{$actualType}` instead");
        } else {
            $variablePath = self::formatVariablePath($payloadVariable, ...$payloadVariableSubPath);
            parent::__construct("Unexpected payload for {$variablePath}, expected `{$expectedType}` but got `{$actualType}` instead");
        }
    }

}