<?php
/**
 *  @author      Peter Fox <peter.fox@ylsideas.co>
 *  @copyright  Copyright (c) YLS Ideas 2018
 */

namespace YlsIdeas\Forecaster;

interface CastingTransformer
{
    /**
     * @param string $in
     * @param string $out
     * @param array $item
     * @param array $processed
     * @return mixed
     */
    public function cast(string $in, string $out, array $item, array $processed);
}
