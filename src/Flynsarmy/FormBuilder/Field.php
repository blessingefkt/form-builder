<?php namespace Flynsarmy\FormBuilder;

use Flynsarmy\FormBuilder\Exceptions\UnknownType;
use Illuminate\Support\Str;

/**
 * Class Field
 * @property string $id
 * @property string $type
 * @property string $value
 */
class Field extends Element
{
	protected $slug;
    protected $type;
    protected $value;
    protected $properties = array(
        'row' => null,
        'rowSize' => 0,
        'baseNames' => [],
    );

    /**
     * Creates a new form field.
     *
     * @param array $id
     * @param string $type
     * @param string|null $value
     * @param array $attributes
     */
	public function __construct($id, $type, $value = null, array $attributes = [])
	{
        parent::__construct($attributes);
        $this->id = $id;
        $this->type($type);
        $this->value($value);
    }

    /**
     * Set the field type.
     *
     * @param  string $type
     *
     * @return \Flynsarmy\FormBuilder\Field
     */
    public function type($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Set the field value.
     *
     * @param  string $value
     *
     * @return \Flynsarmy\FormBuilder\Field
     */
    public function value($value)
    {
        $this->value = $value;
        return $this;
    }

    /**
     * @param $key
     * @param $value
     * @return $this
     */
    public function set($key, $value)
    {
        if (method_exists($this, $method = 'onSet'.Str::studly($key)))
            $this->{$method}($value);
        elseif ( in_array($key, array('id', 'type', 'value')) )
             $this->$key = $value;
        else
        {
            if ($this->isProperty($key))
                $this->setProperty($key, $value);
            else
                $this->setAttr($key, $value);
        }
        return $this;
    }

    /**
     * Get an attribute from the container.
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if ( in_array($key, array('id', 'type', 'value')) )
            return $this->$key;

        if ($this->isProperty($key))
            $value = $this->getProperty($key, $default);
        else
            $value = $this->getAttr($key, $default);

        if (method_exists($this, $method = 'onGet'.Str::studly($key)))
            return $this->{$method}($value ?: $default);

        return $value;
    }

    /**
     * @param array $properties
     * @return $this
     */
    public function setProperties(array $properties)
    {
        foreach ($properties as $name => $value)
        {
            $this->setProperty($name, $value);
        }
        return $this;
    }

    /**
     * @param $properties
     * @return $this
     */
    public function appendProperties($properties)
    {
        $this->properties = array_merge($this->properties, $properties);
        return $this;
    }

    /**
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @param $name
     * @param $value
     * @return $this
     */
    public function setProperty($name, $value)
    {
        $this->properties[$name] = $value;
        return $this;
    }
    /**
     * @param $name
     * @param mixed $default
     * @return mixed
     */
    public function getProperty($name, $default = null)
    {
        if (!$this->hasProperty($name)) return $default;
        $value = $this->properties[$name];
        return value($value);
    }

    /**
     * @param $name
     * @return $this
     */
    public function removeProperty($name)
    {
        unset($this->properties[$name]);
        return $this;
    }

    /**
     * @param $key
     * @return bool
     */
    public function isProperty($key)
    {
        return array_key_exists($key, $this->properties);
    }

    /**
     * @param $key
     * @return bool
     */
    public function hasProperty($key)
    {
        return isset($this->properties[$key]);
    }

    /**
     * @param mixed $value
     * @param bool $onTop
     * @return $this
     */
    public function addName($value, $onTop = false)
    {
        $names = $this->getProperty('baseNames');
        if ($names !== false)
        {
            $values = (array) $value;
            foreach ($values as $value)
            {
                if ($onTop)
                    array_unshift($names, $value);
                else
                    array_push($names, $value);
            }
            $this->setProperty('baseNames', $names);
        }
        return $this;
    }

    /**
     * @param $value
     * @return string
     */
    protected function onGetName($value)
    {
        if ($value === false)
            return null;
        if (is_null($value))
            $value = $this->getProperty('slug');
        $baseNames = $this->getProperty('baseNames');
        if ($baseNames === false) $baseNames = [];
        return $value = $this->makeFieldName($value, $baseNames, $this->multiple);
    }

    /**
     * @return mixed
     */
    public function safeName()
    {
        return str_replace(array('.', '[]', '[', ']'), array('_', '', '.', ''), $this->name);
    }

    /**
     * @param $name
     * @param $baseNames
     * @param $multiple
     * @return string
     */
    protected function makeFieldName($name, $baseNames, $multiple)
    {
        $baseNames = (array) $baseNames;
        $baseNames[] = $name;
        $name = null;
        foreach ($baseNames as $_name) {
            if (!$name)
            {
                if ($_name === false)
                    $name = -1;
                else
                    $name = $_name;
            }
            elseif($name == -1)
            {
                $name = $_name;
                break;
            }
            else
                $name .= '['.$_name.']';
        }

        if ($multiple) return $name.'[]';
        return $name;
    }

    /**
     * Return a property or attribute
     *
     * @param $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->get($name);
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
        $this->set($name, $value);
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
		if ( method_exists($this, $name) )
			return call_user_func_array(array($this, $name), $arguments);

		if ( !sizeof($arguments) )
			$this->set($name, true);
        elseif ($name == 'class')
            $this->addClass($arguments);
		elseif ( sizeof($arguments) == 1 )
            $this->set($name, $arguments[0]);
		else
            $this->setProperty($name, $arguments);

		return $this;
	}
}