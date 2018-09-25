# Forecaster :partly_sunny: [![Build Status](https://travis-ci.org/ylsideas/forecaster.svg?branch=master)](https://travis-ci.org/ylsideas/forecaster)

Forecaster is a library for manipulating and casting associative arrays in PHP.

While the project makes use of a class and helpers found in Laravel it can be used in non-Laravel
projects as it only depends upon the [Tighten Co's Collect package](https://github.com/tightenco/collect)
and not the Laravel Support package meaning it's compatible with any framework or stand-alone project.

## Installation

Forecaster is compatible and tested with PHP 7.1 and greater.

This package should be installed through [composer](https://getcomposer.org/) using the following 
command:

```bash
composer require ylsideas/forecaster
```

## Usage

You can use Forecaster to process arrays with casting and array placement.
For example all the following basic types are included, `int`, `integer`, 
`float`, `double`, `real`, `string`, `boolean` and `bool`. You can also 
ignore the data type argument so no casting is done but the key will still
be included.

```php
$result = forecast([
    'a-string-int' => '10',
    'a-string-float' => '1.5',
    'an-int' => 10,
    'another-int' => 1,
    'do-not-touch' => '11'
])
    ->cast('a-string-int', 'anInt', 'int')
    ->cast('a-string-float', 'aFloat', 'float')
    ->cast('an-int, 'aString', 'string')
    ->cast('another-int', 'aBoolean', 'bool')
    ->cast('do-not-touch', 'doNotTouch')
    ->get();

// $results to

[
    'anInt' => 10,
    'aFloat' => 1.5,
    'aString' => '10',
    'aBoolean' => true,
    'doNotTouch' => '11',
]   
```

Forecaster can also handle more complex array structures using dot notation.

```php
$result = forecast([
    'onions' => [
        'have' => [
            'layers' => true,
        ]
    ]
])
    ->cast('onions.have.layers', 'ogres.do.to')
    ->get();
    
// results to

[
    'orgres' => [
        'do' => [
            'to' => true,
        ]
    ]
]               
```

You need not use just arrays, you may also use an object or a mix
of objects and arrays with the same dot notation.

```php
$object = new stdClass();
$object->objField = [
    'arrField' => '10',
];

$result = forecast($object)
    ->cast('objField.arrField', 'my_field', 'int')
    ->get();
    
// results to

[
    'my_field' => 10,
]               
```

### Add your own fixed transformers

You can apply your own transformers to the Forecaster class statically 
making them available to all instances created.

```php
Forecaster::transformer('csv', function ($value) {
    return str_getcsv($value);
});

$results = forecast([
    'test' => '1, 2, 3',
])
    ->cast('test', 'output', 'csv')
    ->get();
    
// results to

[
    'output' => ['1', '2', '3']
]
```

### Use functions for casting on the fly

```php
$results = forecast([
    'test' => '1, 2, 3',
])
    ->cast('test', 'output', function ($value) {
        return str_getcsv($value);
    })
    ->get();
    
// results to

[
    'output' => ['1', '2', '3']
]
```

### Use classes for more complex casting

You can define transformers if you need to perform more complex logic
that you wish to reuse.

```php
public class CsvTransformer implements CastingTransformer
{
    public function cast(string $in, string $out, array $item, array $processed)
    {
        return str_getcsv($item[$in]);
    }
}
```

Which can then be applied when using forecast.

```php
$results = forecast([
    'test' => '1, 2, 3',
])
    ->cast('test', 'output', new CsvTransformer())
    ->get();
    
// results to

[
    'output' => ['1', '2', '3']
]
```

### Conditional transformations

Sometimes you might want to only perform some casting based on certain
conditions. Forecaster provides a function for this that will only execute 
when the condition is truthy (e.g. == true).

```php
$processed = Forecaster::make([
    'test' => '10',
])
    ->when(true, function (Forecaster $caster) {
        $caster->cast('test', 'output', 'int');
    })
    ->get();
    
// results to

[
    'output' => 10, 
]    
```

You may also use a function to resolve the conditional.

```php
$processed = Forecaster::make([
    'test' => '10',
])
    ->when(
        function ($item) {
            return $item['test'] > 1;
        }, 
        function (Forecaster $caster) {
            $caster->cast('test', 'output', 'int');
        }
    )
    ->get();
    
// results to

[
    'output' => 10, 
]    
```

### Cast Into objects

If you're rather turn the result into an object of your choice you
can provide a class string to the get method.

```php
$results = forecast([
    'test' => '10',
])
    ->cast('test', 'output')
    ->get(SomeClass::class);
```

You can also provide the string `object` as a parameter which will
instruct the forecaster instance to cast the array into a stdClass object.

```php
$object = forecast([
    'test' => '10',
])
    ->cast('test', 'output')
    ->get('object');
```

There is also the option to resolve this using a function.

```php
$results = forecast([
    'test' => '10',
])
    ->cast('test', 'output')
    ->get(function ($processed) {
        return new SomeClass($processed['output']);
    });
```

### Using it with Laravel/Tighten Collections

If you have an array of items you'd like to cast that's already in
a Laravel/Tighten Collection class a macro is available allowing you
to do it seamlessly.

```php
$collection = collect([
    ['test' => '123.456'],
    ['test' => '789.101112']
])
    ->forecast(function (Forecaster $forecast) {
        $forecast->cast('test', 'output', 'float');
    })
    ->toArray();
    
// results to

[
    ['output' => 123.456],
    ['output' => 789.101112],
]
```

## FAQ

### Why no datetime converter?

Currently we feel this should be implemented by the user and not
apart of the library due to how some developers only use
`datetime` while others would use additional packages like
Carbon or Chronos. We're open to ideas for this as it makes sense. We just
don't want to put forward something that requires a breaking change
early on.

## Testing

Testing for this package is done using PHPUnit. You can run this from
the composer dependencies. Running `vendor/bin/phpunit` will execute phpunit.xml.dist
but you may copy it to phpunit.xml if you wish to change it for your own
testing but please do not commit your version of phpunit.xml as part of any PR.

## Contributing

If you wish to contribute to this project, please read the included
[contribution guide](CONTRIBUTING.md)

## License

This project is covered by the MIT license and can be read about the included
[license.md](LICENSE.md).
