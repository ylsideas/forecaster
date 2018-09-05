<?php
/**
 *  @author      Peter Fox <peter.fox@ylsideas.co>
 *  @copyright  Copyright (c) YLS Ideas 2018
 */

if (! \Illuminate\Support\Collection::hasMacro('forecast')) {
    \Illuminate\Support\Collection::macro('forecast', function (callable $callable) {
        return $this->map(function ($item) use ($callable) {
            $Forecaster = forecast($item);
            $callable($Forecaster);
            return $Forecaster->get();
        });
    });
}
