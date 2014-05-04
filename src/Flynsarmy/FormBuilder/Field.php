<?php namespace Flynsarmy\FormBuilder;

/**
 * Class Field
 * @property string $slug
 * @property string $type
 * @property string|mixed $value
 * @property string $description
 * @property string $label
 * @property array $options
 * @property array $baseNames
 * @property string $row
 * @property int $rowSize
 */
class Field extends Element
{
    /**
     * @var string
     */
    protected $slug;
    protected $properties = array(
        'type' => null,
        'label' => null,
        'description' => null,
        'options' => [],
        'row' => null,
        'rowSize' => 0,
        'value' => null,
        'baseNames' => [],
    );

    /**
     * Creates a new form field.
     *
     * @param array $slug
     * @param string $type
     * @param string|null $value
     * @param array $attributes
     * @param array $properties
     */
    public function __construct($slug, $type, $value = null, array $attributes = [], array $properties = [])
    {
        parent::__construct($attributes, $properties);
        $this->slug = $slug;
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
            $value = $this->slug;
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