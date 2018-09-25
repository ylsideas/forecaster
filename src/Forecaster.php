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
        $value = null;

        if ($type === null) {
            $value = data_get($this->item, $field);
        } elseif (is_string($type) && in_array($type, self::$transformers)) {
            $value = $this->castPrimitives(
                $type,
                data_get($this->item, $field)
            );
        } elseif (is_string($type) && key_exists($type, self::$customTransformers)) {
            $transformer = self::$customTransformers[$type];
            $value = $transformer(data_get($this->item, $field));
        } elseif (is_callable($type)) {
            $value = $type(data_get($this->item, $field));
        } elseif ($type instanceof CastingTransformer) {
            $value = $type->cast($field, $processedField, $this->item, $this->processed);
        }

        Arr::set(
            $this->processed,
            $processedField,
            $value
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
     * @return array
     */
    public function get()
    {
        return $this->processed;
    }

    /**
     * @param string|Closure $class
     * @return mixed
     * @throws ErrorException
     */
    public function into($class)
    {
        if (is_callable($class)) {
            return $class($this->processed);
        } elseif (class_exists($class)) {
            return new $class($this->processed);
        }

        throw new ErrorException("Class [$class] could not be resolved");
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
