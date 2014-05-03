<?php namespace Flynsarmy\FormBuilder;

use Closure;
use Flynsarmy\FormBuilder\Exceptions\FieldNotFound;
use Flynsarmy\FormBuilder\Exceptions\RendererNotFound;

class FormBuilderManager
{
    use Traits\Bindable;
    protected $renderers = [];
    protected $macros = [];
    protected $defaultRenderer = null;

    /**
     * Create a new Form
     *
     * @param  Closure $callback Optional closure accepting a Form object
     * @param string $renderer
     * @return \Flynsarmy\FormBuilder\Form
     */
    public function form(callable $callback = null, $renderer = null)
    {
        $form = new Form($this, $renderer ?: $this->defaultRenderer);

        foreach ( $this->bindings as $event => $bindable_callback )
            $form->bind($event, $bindable_callback);

        if ($callback) call_user_func($callback, $form);

        return $form;
    }

    /**
     * @param $type
     * @param Field $field
     * @param FormRenderer $renderer
     * @return mixed
     * @throws Exceptions\FieldNotFound
     */
    public function callMacro($type, Field $field, FormRenderer $renderer = null)
    {
        if (!$this->isMacro($type))
            throw new FieldNotFound($type);
        $macro = $this->macros[$type];
        return call_user_func($macro, $field, $renderer);
    }

    /**
     * @param $name
     * @param $callable
     * @return bool
     */
    public function addMacro($name, $callable)
    {
        $this->macros[$name] = $callable;
    }

    /**
     * @param $name
     * @return bool
     */
    public function isMacro($name)
    {
        return isset($this->macros[$name]);
    }

    /**
     * @param $name
     * @param callable $callback
     */
    public function addRenderer($name, \Closure $callback)
    {
        $this->renderers[$name] = $callback;
    }

    /**
     * @param $name
     * @return callable
     * @throws Exceptions\RendererNotFound
     */
    public function getRenderer($name)
    {
        if (!isset($this->renderers[$name]))
            throw new RendererNotFound($name);
        return $this->renderers[$name];
    }

    /**
     * @param null $defaultRenderer
     */
    public function setDefaultRenderer($defaultRenderer)
    {
        $this->defaultRenderer = $defaultRenderer;
    }

    /**
     * @return null
     */
    public function getDefaultRenderer()
    {
        return $this->defaultRenderer;
    }
}