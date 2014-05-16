<?php namespace Iyoworks\FormBuilder\Traits;

use Iyoworks\FormBuilder\BinderInterface;

trait Bindable
{
    /**
     * @var array|callable[]
     */
    protected $bindings = [];
    /**
     * @var array|BinderInterface[]
     */
    protected $binders = [];
    /**
     * @var array|string[]
     */
    protected $binderMethods =  ['newField', 'beforeField', 'afterField',
        'beforeRow', 'afterRow', 'afterForm', 'beforeForm'];

    /**
     * @param BinderInterface $binder
     * @param string $name  defaults to binder class name
     * @return $this
     */
    public function addBinder(BinderInterface $binder, $name = null)
    {
        if (!$name)  $name = get_class($binder);
        $this->binders[$name] = $binder;
        return $this;
    }

    /**
     * @param $event
     * @param mixed $default
     * @return mixed|null
     */
    public function getBinding($event, $default = NULL)
    {
        if (isset($this->bindings[$event]))
            return $this->bindings[$event];

        return $default;
    }

    /**
     * @param $event
     * @param callable $callback
     * @return $this
     */
    public function bind($event, callable $callback)
    {
        $this->bindings[$event] = $callback;
        return $this;
    }

    /**
     * @param $event
     * @return $this
     */
    public function removeBinder($event)
    {
        unset($this->binders[$event]);
        return $this;
    }

    /**
     * @param $event
     * @return $this
     */
    public function unbind($event)
    {
        unset($this->bindings[$event]);
        return $this;
    }

    /**
     * @return mixed|string
     */
    public function fire()
    {
        $args = func_get_args();
        $event = array_shift($args);
        $output = '';

        if (in_array($event, $this->binderMethods))
        {
            foreach ($this->binders as $binder)
            {
                $output .= call_user_func_array([$binder, $event], $args);
            }
        }

        if ( isset($this->bindings[$event]) )
        {
            $output .= call_user_func_array($this->bindings[$event], $args);
        }
        return $output;
    }
}