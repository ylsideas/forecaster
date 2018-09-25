<?php
/**
 *  @author      Peter Fox <peter.fox@ylsideas.co>
 *  @copyright  Copyright (c) YLS Ideas 2018
 */

namespace Tests;

use PHPUnit\Framework\TestCase;
use YlsIdeas\Forecaster\CastingTransformer;
use YlsIdeas\Forecaster\Forecaster;

class ForecasterTest extends TestCase
{
    public static function setUpBeforeClass()
    {
        Forecaster::transformer('csv', function ($value) {
            return str_getcsv($value);
        });
    }

    public function test_it_can_be_made_from_a_static_context()
    {
        $caster = Forecaster::make([]);

        $this->assertInstanceOf(Forecaster::class, $caster);
    }

    /**
     * @dataProvider primitives
     * @param string $field
     * @param string $value
     * @param string $type
     * @param string $processedField
     * @param mixed $expected
     * @throws \ErrorException
     */
    public function test_it_can_convert_primitive_fields($field, $value, $type, $processedField, $expected)
    {
        $processed = Forecaster::make([
            $field => $value,
        ])
            ->cast($field, $processedField, $type)
            ->get();

        $this->assertArrayHasKey($processedField, $processed);
        $this->assertSame($expected, $processed[$processedField]);
    }

    public function primitives()
    {
        return [
            ['example', 10, null, 'processed', 10],
            ['example', '10', 'int', 'processed', 10],
            ['example', '10', 'integer', 'processed', 10],
            ['example', '11.1', 'float', 'processed', 11.1],
            ['example', '11.1', 'double', 'processed', 11.1],
            ['example', '11.1', 'real', 'processed', 11.1],
            ['example', 'true', 'boolean', 'processed', true],
            ['example', 'true', 'bool', 'processed', true],
        ];
    }

    public function test_it_can_register_custom_transformers()
    {
        $processed = Forecaster::make([
            'test' => '1,2,3',
        ])
            ->cast('test', 'output', 'csv')
            ->get();

        $this->assertArrayHasKey('output', $processed);
        $this->assertSame(['1', '2' , '3'], $processed['output']);
    }

    public function test_it_blocks_overwriting_of_custom_transformers()
    {
        $this->expectException(\ErrorException::class);

        Forecaster::transformer('csv', function ($value) {
            return str_getcsv($value);
        });
    }

    public function test_it_blocks_overwriting_of_built_in_transformers()
    {
        $this->expectException(\ErrorException::class);

        Forecaster::transformer('int', function ($value) {
            return (int) $value;
        });
    }

    /**
     * @dataProvider checkForExistingTransformers
     * @param $type
     * @param $expected
     */
    public function test_it_can_check_for_previous_transformers($type, $expected)
    {
        $this->assertSame($expected, Forecaster::hasTransformer($type));
    }

    public function checkForExistingTransformers()
    {
        return [
            ['csv', true],
            ['int', true],
            ['test', false],
        ];
    }

    public function test_it_can_use_callables_as_type_arguments()
    {
        $processed = Forecaster::make([
            'test' => '1,2,3',
        ])
            ->cast('test', 'output', function ($value) {
                return str_getcsv($value);
            })
            ->get();

        $this->assertArrayHasKey('output', $processed);
        $this->assertSame(['1', '2' , '3'], $processed['output']);
    }

    public function test_it_can_use_implementations_of_casting_transformers_as_a_type()
    {
        $processed = Forecaster::make([
            'test' => '1,2,3',
        ])
            ->cast('test', 'output', new TestableCastingTransformer())
            ->get();

        $this->assertArrayHasKey('output', $processed);
        $this->assertSame(['1', '2' , '3'], $processed['output']);
    }

    public function test_it_can_convert_the_processed_into_a_type_of_class_using_into()
    {
        /** @var TestableCastIntoClass $object */
        $object = Forecaster::make([
            'test' => '10',
        ])
            ->cast('test', 'output', 'int')
            ->into(TestableCastIntoClass::class);

        $this->assertArrayHasKey('output', $object->getItem());
        $this->assertSame(10, $object->getItem()['output']);
    }

    public function test_it_can_convert_the_processed_into_a_std_object()
    {
        /** @var \stdClass $object */
        $object = Forecaster::make([
            'test' => '10',
        ])
            ->cast('test', 'output', 'int')
            ->get('object');

        $this->assertObjectHasAttribute('output', $object);
        $this->assertSame(10, $object->output);
    }

    public function test_it_can_convert_the_processed_into_a_type_of_class()
    {
        /** @var TestableCastIntoClass $object */
        $object = Forecaster::make([
            'test' => '10',
        ])
            ->cast('test', 'output', 'int')
            ->get(TestableCastIntoClass::class);

        $this->assertArrayHasKey('output', $object->getItem());
        $this->assertSame(10, $object->getItem()['output']);
    }

    public function test_it_can_convert_the_processed_into_a_type_of_class_from_closure()
    {
        /** @var TestableCastIntoClass $object */
        $object = Forecaster::make([
            'test' => '10',
        ])
            ->cast('test', 'output', 'int')
            ->get(function ($processed) {
                return new TestableCastIntoClass($processed);
            });

        $this->assertArrayHasKey('output', $object->getItem());
        $this->assertSame(10, $object->getItem()['output']);
    }

    public function test_it_can_handle_fetching_multiple_levels_of_arrays()
    {
        $processed = Forecaster::make([
            'test' => [
                'value' => '10'
            ]
        ])
            ->cast('test.value', 'output', 'int')
            ->get();

        $this->assertArrayHasKey('output', $processed);
        $this->assertSame(10, $processed['output']);
    }

    public function test_it_can_handle_setting_multiple_levels_of_arrays()
    {
        $processed = Forecaster::make([
            'key' => '10',
            'value' => '10',
        ])
            ->cast('key', 'output.key', 'int')
            ->cast('value', 'output.value', 'int')
            ->get();

        $this->assertArrayHasKey('output', $processed);
        $this->assertArrayHasKey('key', $processed['output']);
        $this->assertArrayHasKey('value', $processed['output']);
        $this->assertSame(10, $processed['output']['key']);
        $this->assertSame(10, $processed['output']['value']);
    }

    public function test_it_can_work_with_conditional_closure_when_condition_is_true()
    {
        $processed = Forecaster::make([
            'test' => '10',
        ])
            ->when(true, function (Forecaster $caster) {
                $caster->cast('test', 'output', 'int');
            })
            ->get();

        $this->assertArrayHasKey('output', $processed);
        $this->assertSame(10, $processed['output']);
    }

    public function test_it_can_work_with_conditional_closure_when_condition_is_false()
    {
        $processed = Forecaster::make([
            'test' => '10',
        ])
            ->when(false, function (Forecaster $caster) {
                $caster->cast('test', 'output', 'int');
            })
            ->get();

        $this->assertArrayNotHasKey('output', $processed);
    }

    public function test_it_can_work_with_conditional_closure_when_condition_resolves_from_closure()
    {
        $processed = Forecaster::make([
            'test' => '10',
        ])
            ->when(
                function () {
                    return true;
                },
                function (Forecaster $caster) {
                    $caster->cast('test', 'output', 'int');
                }
            )
            ->get();

        $this->assertArrayHasKey('output', $processed);
        $this->assertSame(10, $processed['output']);
    }
}

class TestableCastingTransformer implements CastingTransformer
{
    public function cast(string $in, string $out, array $item, array $processed)
    {
        return str_getcsv($item[$in]);
    }
}

class TestableCastIntoClass
{
    protected $item;

    public function __construct(array $item)
    {
        $this->item = $item;
    }

    /**
     * @return array
     */
    public function getItem()
    {
        return $this->item;
    }
}
