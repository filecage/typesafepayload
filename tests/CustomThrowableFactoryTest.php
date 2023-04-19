<?php

use PHPUnit\Framework\TestCase;
use TypesafePayload\TypesafePayload\BadPayloadException;
use TypesafePayload\TypesafePayload\ThrowableFactory;

class CustomThrowableFactoryTest extends TestCase {

    function testExpectsThrowableFactoryToBeUsed () {
        $errorFactory = new class implements ThrowableFactory {
            function createThrowable (string $expectedType, string $actualType, int|string|null $payloadVariable = null, string|int ...$payloadVariableSubPath): Throwable {
                return new Error("$expectedType $actualType " . BadPayloadException::formatVariablePath($payloadVariable, ...$payloadVariableSubPath));
            }
        };

        $payload = new TypesafePayload\TypesafePayload\TypesafePayload((object) ['foo' => ['bar']], $errorFactory);

        $this->expectException(Error::class);
        $this->expectExceptionMessage('integer string $foo[0]');

        $payload->property('foo')->index(0)->asInteger();
    }

}