<?php
/**
 *  @author      Peter Fox <peter.fox@ylsideas.co>
 *  @copyright  Copyright (c) YLS Ideas 2018
 */

namespace YlsIdeas\Forecaster;

use Closure;
use ErrorException;
use Illuminate\Support\Arr;

/**
 * Class Forecaster
 * @package YlsIdeas\Forecaster
 */
class Forecaster
{
    /**
     * @var array
     */
    protected static $transformers = [
        'int',
        'integer',
        'real',
        'double',
        'float',
        'boolean',
        'bool',
    ];

    /**
     * @var array
     */
    protected static $customTransformers = [];

    /**
     * @var array|object
     */
    protected $item;

    /**
     * @var array
     */
    protected $processed = [];

    /**
     * @param array|object $item
     * @return Forecaster
     */
    public static function make($item)
    {
        return new self($item);
    }

    /**
     * @param string $type
     * @param callable $transformer
     * @throws ErrorException
     */
    public static function transformer(string $type, callable $transformer)
    {
        if (in_array($type, self::$transformers)) {
            throw new ErrorException("CastingTransformer type [$type] is a built in transformer");
        }

        if (key_exists($type, self::$customTransformers)) {
            throw new ErrorException("CastingTransformer type [$type] is already defined");
        }

        self::$customTransformers[$type] = $transformer;
    }

    /**
     * @param string $type
     * @return bool
     */
    public static function hasTransformer(string $type)
    {
        return in_array($type, self::$transformers) || key_exists($type, self::$customTransformers);
    }

    /**
     * Forecaster constructor.
     * @param array|object $item
     */
    public function __construct($item)
    {
        $this->item = $item;
    }

    /**
     * @param string $field
     * @param string $processedField
     * @param null|string|Closure|CastingTransformer $type
     * @return $this
     */
    public function cast(string $field, string $processedField, $type = null)
    {
        $value = $this->castValue(data_get($this->item, $field), $field, $processedField, $type);

        Arr::set(
            $this->processed,
            $processedField,
            $value
        );

        return $this;
    }

    /**
     * @param string $field
     * @param string $processedField
     * @param null|string|Closure|CastingTransformer $type
     * @return $this
     * @throws ErrorException
     */
    public function castAll(string $field, string $processedField, $type = null)
    {
        $itemData = data_get($this->item, $field);

        if (! is_array($itemData)) {
            throw new ErrorException("Field [$field] does not provide an array");
        }

        $data = collect($itemData)
            ->map(function ($item) use ($field, $processedField, $type) {
                return $this->castValue($item, $field, $processedField, $type);
            })
            ->toArray();

        Arr::set(
            $this->processed,
            $processedField,
            $data
        );

        return $this;
    }

    /**
     * @param mixed $condition
     * @param callable $callable
     * @return Forecaster
     */
    public function when($condition, callable $callable)
    {
        if (is_callable($condition)) {
            $condition = $condition($this->item, $this->processed);
        }

        if ($condition) {
            $callable($this);
        }
        return $this;
    }

    /**
     * @param string|Closure $class
     * @return array|object
     * @throws ErrorException
     */
    public function get($class = null)
    {
        if (is_callable($class)) {
            return $class($this->processed);
        } elseif (class_exists($class)) {
            return new $class($this->processed);
        } elseif ($class === 'object') {
            return (object) $this->processed;
        }

        if ($class !== null) {
            throw new ErrorException("Class [$class] could not be resolved");
        }

        return $this->processed;
    }

    /**
     * @deprecated
     *
     * @param string|Closure $class
     * @return mixed
     * @throws ErrorException
     */
    public function into($class)
    {
        return $this->get($class);
    }

    /**
     * @param mixed $data
     * @param string $field
     * @param string $processedField
     * @param null|string|Closure|CastingTransformer $type
     * @return bool|float|int|mixed|null|string
     */
    protected function castValue($data, $field, $processedField, $type = null)
    {
        $value = null;

        if ($type === null) {
            $value = $data;
        } elseif (is_string($type) && in_array($type, self::$transformers)) {
            $value = $this->castPrimitives(
                $type,
                $data
            );
        } elseif (is_string($type) && key_exists($type, self::$customTransformers)) {
            $transformer = self::$customTransformers[$type];
            $value = $transformer($data);
        } elseif (is_callable($type)) {
            $value = $type($data);
        } elseif ($type instanceof CastingTransformer) {
            $value = $type->cast($field, $processedField, $this->item, $this->processed);
        }

        return $value;
    }

    /**
     * @param $type
     * @param $value
     * @return bool|float|int|string
     */
    protected function castPrimitives($type, $value)
    {
        switch ($type) {
            case 'int':
            case 'integer':
                return (int) $value;
            case 'real':
            case 'float':
            case 'double':
                return (float) $value;
            case 'string':
                return (string) $value;
            case 'bool':
            case 'boolean':
                return (bool) $value;
            default:
                return $value;
        }
    }
}
