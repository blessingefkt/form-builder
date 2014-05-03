<?php namespace Flynsarmy\FormBuilder\Traits;

use Flynsarmy\FormBuilder\BinderInterface;

trait Bindable
{
    protected $bindings = array();


    /**
     * @param BinderInterface $binder
     * @return $this
     */
    public function addBinder(BinderInterface $binder)
    {
        $class = get_class($binder);
        $this->bind('newField', [$binder, 'newField'], $class);
        $this->bind('beforeField', [$binder, 'beforeField'], $class);
        $this->bind('afterField', [$binder, 'afterField'], $class);
        $this->bind('beforeRow', [$binder, 'beforeRow'], $class);
        $this->bind('afterRow', [$binder, 'afterRow'], $class);
        $this->bind('afterForm', [$binder, 'afterForm'], $class);
        $this->bind('beforeForm', [$binder, 'beforeForm'], $class);
        return $this;
    }

    /**
     * @param $binding
     * @param mixed $default
     * @return array
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
    public function unbindId($identifier)
    {
        foreach($this->bindings as $event => $callables)
        {
            unset($callables[$identifier]);
            $this->bindings[$event] = $callables;
        }
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
        if ( isset($this->bindings[$event]) )
            foreach ($this->bindings[$event] as $callable)
            {
                $output .= call_user_func_array($callable, $args);
            }
        return $output;
    }
}