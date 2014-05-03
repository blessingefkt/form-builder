<?php namespace Flynsarmy\FormBuilder\Traits;

use Closure;

trait Bindable
{
	protected $bindings = array();

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
     * @return $this
     */
    public function bind($event, Closure $callback)
	{
		$this->bindings[$event][] = $callback;

		return $this;
	}

    /**
     * @param $event
     * @return $this
     */
    public function unbind($event)
	{
		if ( isset($this->bindings[$event]) )
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