<?php namespace Flynsarmy\FormBuilder;

use Flynsarmy\FormBuilder\Exceptions\UnknownType;
use Illuminate\Html\FormBuilder;

class Element {
    protected $type;
    protected $args = array();

    /**
     * @var \Illuminate\Html\FormBuilder
     */
    protected static $formBuilder;

    /**
     * @param \Illuminate\Html\FormBuilder $formBuilder
     */
    public static function setFormBuilder($formBuilder)
    {
        self::$formBuilder = $formBuilder;
    }

    /**
     * @return \Illuminate\Html\FormBuilder
     */
    public static function getFormBuilder()
    {
        return self::$formBuilder;
    }

    public function render()
    {
        if ( !$this->type )
            throw new UnknownType("You must set a field type for an element");

        return call_user_func_array(array(static::$formBuilder, $this->type), $this->args);
    }
} 