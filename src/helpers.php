<?php
/**
 *  @author      Peter Fox <peter.fox@ylsideas.co>
 *  @copyright  Copyright (c) YLS Ideas 2018
 */

if (! function_exists('forecast')) {
    function forecast($items)
    {
        return \YlsIdeas\Forecaster\Forecaster::make($items);
    }
}
