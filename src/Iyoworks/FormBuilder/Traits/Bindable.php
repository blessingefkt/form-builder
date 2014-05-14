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
     * @param $binding
     * @param mixed $default
     * @return mixed|null
     */
    public function getBinding($binding, $default=NULL)
    {
        if ( isset($this->bindings[$binding]))
            return $this->bindings[$binding];

        return $default;
    }

    /**
     * @param $event
     * @param callable $callback
     * @param string $identifier
     * @return $this
     */
    public function bind($event, callable $callback, $identifier = null)
    {
        if ($identifier)
            $this->bindings[$event][$identifier] = $callback;
        else
            $this->bindings[$event][] = $callback;

        return $this;
    }

    /**
     * @param $identifier
     * @return $this
     */
    public function removeBinder($identifier)
    {
        unset($this->binders[$identifier]);
        return $this;
    }

    /**
     * @param $event
     * @param string $identifier
     * @return $this
     */
    public function unbind($event, $identifier = null)
    {
        if (!is_null($identifier))
            unset($this->bindings[$event][$identifier]);
        else
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
            foreach ($this->bindings[$event] as $callable)
            {
                $output .= call_user_func_array($callable, $args);
            }
        }
        return $output;
    }
}