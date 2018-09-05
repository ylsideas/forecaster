<?php
/**
 *  @author      Peter Fox <peter.fox@ylsideas.co>
 *  @copyright  Copyright (c) YLS Ideas 2018
 */

namespace Tests;

use PHPUnit\Framework\TestCase;

class HelpersTest extends TestCase
{
    public function test_the_Forecaster_helper_makes_a_Forecaster()
    {
        $processed = forecast([
            'test' => '10',
        ])
            ->cast('test', 'output', 'int')
            ->get();

        $this->assertArrayHasKey('output', $processed);
        $this->assertSame(10, $processed['output']);
    }
}
