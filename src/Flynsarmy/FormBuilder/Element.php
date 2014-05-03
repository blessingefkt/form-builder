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
     * @return $this
     */
    public function setAttributes($attributes)
    {
        $this->attributes = $attributes;
        return $this;
    }

    /**
     * @return array
     */
    public function getAttributes()
    {
        $attributes = $this->attributes;
        $attributes['class'] = join(' ', array_pull($attributes, 'class', []));
        return $attributes;
    }

    /**
     * Return a property or attribute
     *
     * @param $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->getAttr($name);
    }

    /**
     * Set a property or attribute
     *
     * @param $name
     * @param $value
     * @return mixed
     */
    public function __set($name, $value)
    {
        $this->setAttr($name, $value);
    }

    /**
     * Lets us add custom field settings to be used during the render process.
     *
     * @param  string $name      Setting name
     * @param  array  $arguments Setting value(s)
     *
     * @return \Flynsarmy\FormBuilder\Field
     */
    public function __call($name, $arguments)
    {
        if ( !sizeof($arguments) )
            $this->setAttr($name, true);
        elseif ($name == 'class')
            $this->addClass($arguments);
        elseif ( sizeof($arguments) == 1 )
            $this->setAttr($name, $arguments[0]);

        return $this;
    }
}
