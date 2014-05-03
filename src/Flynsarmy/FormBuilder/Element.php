<?php namespace Flynsarmy\FormBuilder;

use Flynsarmy\FormBuilder\Exceptions\UnknownType;
use Illuminate\Html\FormBuilder;

/**
 * Class Element
 */
class Element {
    protected $attributes = array();

    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
    }

    /**
     * @param array $attributes
     * @return $this
     */
    public function mergeAttributes(array $attributes)
    {
        $this->attributes = array_merge_recursive($this->attributes, $attributes);
        return $this;
    }

    /**
     * @param $key
     * @param null $default
     * @return mixed
     */
    public function getAttr($key, $default = null)
    {
        if (array_key_exists($key, $this->attributes))
            $value = $this->attributes[$key];
        else
            $value = $default;
        return value($value);
    }

    /**
     *
     * @param $key
     * @param $value
     * @return $this
     */
    public function setAttr($key, $value)
    {
        $this->attributes[$key] = $value;
        return $this;
    }

    /**
     * Add a class
     * @param $class
     * @return $this
     */
    public function addClass($class)
    {
        if (is_array($class))
        {
            array_map([$this, 'addClass'], $class);
        }
        else
        {
            $classes = (array) array_get($this->attributes, 'class', []);
            if (!is_array($class))
                $class = explode(' ', $class);
            $classes = array_merge($classes, $class);
            $this->attributes['class'] = array_unique($classes);
        }
        return $this;
    }

    /**
     * Remove a class
     * @param $class
     * @return $this
     */
    public function removeClass($class)
    {
        if (is_array($class))
        {
            array_map([$this, 'removeClass'], $class);
        }
        else
        {
            $classes = (array) array_get($this->attributes, 'class', []);
            if ($key = array_search($class, $classes))
            {
                unset($classes[$key]);
            }
            $this->attributes['class'] = $classes;
        }
        return $this;
    }

    /**
     * @param array $attributes
     */
    public function setAttributes($attributes)
    {
        $this->attributes = $attributes;
    }

    /**
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }
}
