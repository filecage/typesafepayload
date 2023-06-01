# Type-Safe Payload
This is a utility for easy, type-safe access to arbitrary data structures.
It can be instantiated with any mixed value (payload) and has methods to
navigate through structures and access its values in a type-safe manner.

Whenever the payload walker encounters that the requested data can not be
retrieved from the given payload, an exception is thrown. This is to keep
the type-safe promise: accessing a value with an expected type will always
return that value in the expected type _or_ throw an exception.

## Installation
via composer
```shell
$ composer require typesafepayload/typesafepayload
```

## Usage
### Accessing Data
#### Example
```php
$payload = new TypeSafePayload('any data');
$value = $payload->asString(); // returns `any data`
$value = $payload->asInteger(); // throws because value is not an integer
$value = $payload->asBoolean(); // throws because value is not a boolean
```

#### Data Access Methods
- `::asString()` returns payload as `string`
- `::asInteger()` returns payload as `integer`
- `::asBoolean()` returns payload as `boolean`
- `::asStringList()` returns an array of `string` values
- `::asIntegerList()` returns an array of `integer` values
- `::asBooleanList()` returns an array of `boolean` values

It's important to note that these methods do **not** cast any types, even
if they technically could (e.g. from integer to string). The purpose of this 
is to ensure a safe protocol between two APIs. If your application allows for
APIs where integers and strings are interchangeable, then this is not for you.

However, there is one exception: if the given payload is an object implementing
the [`Stringable`](https://php.net/Stringable) interface, this value is accepted.

### Navigating The Structure
#### Example: Object / Map access
```php
$payload = new TypeSafePayload(['foo' => ['bar' => true]]);

$isFooBar = $payload->property('foo')->property('bar')->asBoolean(); // returns `true`
$isBaz = $payload->property('baz')->asBoolean();                     // throws because property baz does not exist
```
#### Example: List Access
```php
$payload = new TypeSafePayload(['coordinates' => [12, 23]]);
$x = $payload->property('coordinates')->index(0)->asInteger(); // returns `12`
$y = $payload->property('coordinates')->index(1)->asInteger(); // returns `23`
$z = $payload->property('coordinates')->index(2)->asInteger(); // throws because index 2 is not set
```

#### Data Navigation Methods
- `::property(string $key)` returns a payload walker for the values of the sub-property `$key`
- `::index(int $index)` returns a payload walker for the values of the index `$index`
- `::iterate()` returns an iterator for each value of the current payload
- `::isEmpty()` returns `true` if the current payload is empty

### Modifying The Data
- `::fillEmpty(mixed $value)` fills the current payload with `$value` if it's empty

## Exception Management
By default, an exception of type [`BadPayloadException`](src/BadPayloadException.php) is thrown.
This behaviour can be controlled by passing a [`ThrowableFactory`](src/ThrowableFactory.php)
in order to use userland exception types instead. This is useful to avoid having to catch library
exceptions and throw a new one again.

The `ThrowableFactory` has to be passed to the `TypesafePayload` instance:
```php
$throwableFactory = new class implements \TypesafePayload\TypesafePayload\ThrowableFactory {
    public function createThrowable(string $expectedType, string $actualType, int|string|null $payloadVariable = null,string|int ...$payloadVariableSubPath) : Throwable {
        // see example below to understand $payloadVariable and $payloadVariableSubPath
        return new MyCustomException("Payload Error: Expected type $expectedType but got $actualType instead");
    }
}

$payload = new TypesafePayload\TypesafePayload\TypesafePayload("my arbitrary payload", $throwableFactory);
```
> ⚠️ Don't throw from the `ThrowableFactory` as this will clutter the stack trace

### Payload Variable Path
The `$payloadVariable` and `$payloadVariableSubPath` contain the property and/or index path that was used
to access the current payload where `string` means property access and `int` means index access.

```php
$somePayload = (object) ['foo' => ['bar' => ['baz', 'boo']]];
$payload = new \TypesafePayload\TypesafePayload\TypesafePayload($somePayload);

// throws with `$payloadVariable` being `foo` and `$payloadVariableSubPath` being `bar`, `2` (as integer)
$payload->property('foo')->property('bar')->index(2)->asBoolean();

// to turn this into a human-readable string, use `BadPayloadException::formatVariablePath()`:
echo \TypesafePayload\TypesafePayload\BadPayloadException::formatVariablePath('foo', 'bar', 2);
// formats to `$foo->bar[2]`
```