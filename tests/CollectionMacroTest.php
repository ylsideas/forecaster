<?php
/**
 *  @author      Peter Fox <peter.fox@ylsideas.co>
 *  @copyright  Copyright (c) YLS Ideas 2018
 */

namespace Tests;

use PHPUnit\Framework\TestCase;
use Illuminate\Support\Collection;
use YlsIdeas\Forecaster\Forecaster;

class CollectionMacroTest extends TestCase
{
    public function test_it_allows_for_the_use_of_Forecaster_with_collections()
    {
        /** @var Collection $collection */
        $collection = collect([
            ['test' => '10'],
            ['test' => '20'],
        ])
            ->forecast(function (Forecaster $caster) {
                $caster->cast('test', 'output', 'int');
            });

        $this->assertInstanceOf(Collection::class, $collection);

        $processed = $collection->get(0);

        $this->assertNotNull($processed);
        $this->assertArrayHasKey('output', $processed);
        $this->assertSame(10, $processed['output']);

        $processed = $collection->get(1);

        $this->assertNotNull($processed);
        $this->assertArrayHasKey('output', $processed);
        $this->assertSame(20, $processed['output']);
    }

    public function test_it_allows_for_specifying_what_to_cast_into()
    {
        /** @var Collection $collection */
        $collection = collect([
            ['test' => '10'],
            ['test' => '20'],
        ])
            ->forecast(function (Forecaster $caster) {
                $caster->cast('test', 'output', 'int');
            }, 'object');

        $this->assertInstanceOf(Collection::class, $collection);

        $processed = $collection->get(0);

        $this->assertNotNull($processed);
        $this->assertInstanceOf(\stdClass::class, $processed);
        $this->assertObjectHasAttribute('output', $processed);
        $this->assertSame(10, $processed->output);

        $processed = $collection->get(1);

        $this->assertNotNull($processed);
        $this->assertInstanceOf(\stdClass::class, $processed);
        $this->assertObjectHasAttribute('output', $processed);
        $this->assertSame(20, $processed->output);
    }
}
