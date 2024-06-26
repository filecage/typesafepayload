<?php


use PHPUnit\Framework\TestCase;
use TypesafePayload\TypesafePayload\BadPayloadException;
use TypesafePayload\TypesafePayload\TypesafePayload;
use TypesafePayload\TypesafePayloadTest\DifferentMockClass;
use TypesafePayload\TypesafePayloadTest\ExtendedMockClass;
use TypesafePayload\TypesafePayloadTest\MockClass;

class TypesafePayloadTest extends TestCase {

    private const TEST_JSON = /** @lang JSON */
        <<<JSON
        {
            "object": {
                "string": "hello world",
                "integer": 42,
                "true": true,
                "false": false
            },
            "stringList": [
                "foo",
                "bar"
            ],
            "integerList": [
                4,
                2
            ],
            "booleanList": [
                true,
                false
            ]
        }
        JSON;

    function testExpectsAllValuesToBeExtracted () {
        $payload = new TypesafePayload(json_decode(self::TEST_JSON));

        $this->assertSame('hello world', $payload->property('object')->property('string')->asString());
        $this->assertSame(42, $payload->property('object')->property('integer')->asInteger());
        $this->assertTrue($payload->property('object')->property('true')->asBoolean());
        $this->assertFalse($payload->property('object')->property('false')->asBoolean());

        $this->assertSame('hello world', $payload->property('object')->property('string')->asStringOrNull());
        $this->assertSame(42, $payload->property('object')->property('integer')->asIntegerOrNull());
        $this->assertTrue($payload->property('object')->property('true')->asBooleanOrNull());
        $this->assertFalse($payload->property('object')->property('false')->asBooleanOrNull());

        $this->assertSame(['foo', 'bar'], $payload->property('stringList')->asStringList());
        $this->assertSame([4, 2], $payload->property('integerList')->asIntegerList());
        $this->assertSame([true, false], $payload->property('booleanList')->asBooleanList());
    }

    function testShouldAccessListDataByIndex () {
        $payload = new TypesafePayload(['foo', 42, false]);

        $this->assertSame('foo', $payload->index(0)->asString());
        $this->assertSame(42, $payload->index(1)->asInteger());
        $this->assertSame(false, $payload->index(2)->asBoolean());
    }

    function testExpectsEmptyValueToBeFilled () {
        $payload = new TypesafePayload(new stdClass());
        $this->assertSame('not empty', $payload->property('empty')->fillEmpty('not empty')->asString());
    }

    function testExpectsEmptyValueToBeRecognisedAsEmpty () {
        $payload = new TypesafePayload((object) ['empty' => null, 'notEmpty' => true]);
        $this->assertTrue($payload->property('empty')->isEmpty());
        $this->assertTrue($payload->property('emptier')->isEmpty());
    }

    function testExpectyEmptyValueToBeFilledWithInnerPayloadData () {
        $payload = new TypesafePayload(new stdClass());
        $this->assertSame('not empty', $payload->property('empty')->fillEmpty(new TypesafePayload('not empty'))->asString());
    }

    function testExpectsStringableToBeOkay () {
        $stringable = new class implements Stringable {function __toString (): string { return 'hello world'; }};
        $payload = new TypesafePayload($stringable);;

        $this->assertSame('hello world', $payload->asString());
    }

    function testExpectsIteratorToThrowForNonIterable () {
        $payload = new TypesafePayload(null);
        $this->expectException(BadPayloadException::class);
        $this->expectExceptionMessage('expected `iterable` but got `NULL` instead');

        // Call `iterator_count` to unwind iterator
        iterator_count($payload->iterate());
    }

    function testExpectsIteratorToThrowForUndefinedIndex () {
        $payload = new TypesafePayload([]);
        $this->expectException(BadPayloadException::class);
        $this->expectExceptionMessage('$[1], expected `integer` but got `(empty)` instead');

        $payload->index(1)->asInteger();
    }

    function testExpectsWalkerToThrowForUndefinedProperty () {
        $payload = new TypesafePayload(new stdClass());
        $this->expectException(BadPayloadException::class);
        $this->expectExceptionMessage('$foo, expected `string` but got `(empty)` instead');

        $payload->property('foo')->asString();
    }

    function testExpectsSameInstanceIfPayloadIsNotEmpty () {
        $payload = new TypesafePayload('not empty');
        $this->assertSame($payload, $payload->fillEmpty('foobar'));
    }

    function testExpectsNullValuesForEmptyPropertyWhenUsingNullableAccessors () {
        $payload = (new TypesafePayload(new stdClass()))->property('does-not-exist');

        $this->assertNull($payload->asBooleanOrNull());
        $this->assertNull($payload->asStringOrNull());
        $this->assertNull($payload->asIntegerOrNull());
        $this->assertNull($payload->asInstanceOfOrNull(MockClass::class));
    }

    function testExpectsNullValuesForNullPayloadWhenUsingNullableAccessors () {
        $payload = new TypesafePayload(null);

        $this->assertNull($payload->asBooleanOrNull());
        $this->assertNull($payload->asStringOrNull());
        $this->assertNull($payload->asIntegerOrNull());
        $this->assertNull($payload->asInstanceOfOrNull(MockClass::class));
    }

    function testExpectsInstanceOf () {
        $payload = new TypesafePayload((object) ['parent' => new MockClass(), 'child' => new ExtendedMockClass()]);
        $this->assertInstanceOf(MockClass::class, $payload->property('parent')->asInstanceOf(MockClass::class));
        $this->assertInstanceOf(MockClass::class, $payload->property('parent')->asInstanceOfOrNull(MockClass::class));
        $this->assertInstanceOf(ExtendedMockClass::class, $payload->property('child')->asInstanceOf(MockClass::class));
        $this->assertInstanceOf(ExtendedMockClass::class, $payload->property('child')->asInstanceOfOrNull(MockClass::class));
    }

    function testExpectsInstanceOfToThrowForDifferentClass () {
        $payload = new TypesafePayload(new DifferentMockClass());
        $this->expectException(BadPayloadException::class);
        $this->expectExceptionMessage('expected `instanceof TypesafePayload\TypesafePayloadTest\MockClass` but got `instanceof TypesafePayload\TypesafePayloadTest\DifferentMockClass` instead');

        $this->assertInstanceOf(MockClass::class, $payload->asInstanceOf(MockClass::class));
    }

}