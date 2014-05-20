<?php namespace Iyoworks\FormBuilder;

use Closure;
use Iyoworks\FormBuilder\Exceptions\FieldNotFound;
use Iyoworks\FormBuilder\Exceptions\RendererNotFound;
use Iyoworks\Support\Str;

class FormBuilderManager {
	use Traits\Bindable;

	protected $renderers = [],
		 $resolvedRenderers = [],
		 $macros = [],
		 $macroInitializers = [],
		 $defaultBinders = [];
	protected $defaultRenderer = null;
	/**
	 * @var Form
	 */
	protected $_form;

	/**
	 * Create a new Form
	 *
	 * @param  Closure $callback Optional closure accepting a Form object
	 * @param string $renderer
	 * @return \Iyoworks\FormBuilder\Form
	 */
	public function form(callable $callback = null, $renderer = null)
	{
		$form = new Form($this, $renderer ? : $this->defaultRenderer);

		foreach ($this->defaultBinders as $binder)
			$form->addBinder($binder);
		foreach ($this->bindings as $event => $bindable_callback)
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
		{
			throw new FieldNotFound($type);
		}
		$macro = $this->macros[$type];
		if ($init = $this->macroInitializers[$type])
		{
			call_user_func($init);
			$this->macroInitializers[$type] = false;
		}
		return call_user_func($macro, $field, $renderer);
	}

	/**
	 * @param string $name
	 * @param callable $callable the function that renders the field
	 * @param callable $initializeCallback called the first time the macro is used
	 *                                      this is ideal for loading assets
	 */
	public function addMacro($name, $callable, callable $initializeCallback = null)
	{
		$this->macros[$name] = $callable;
		$this->macroInitializers[$name] = $initializeCallback;
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
		$this->resolvedRenderers[$name] = false;
	}

	/**
	 * @param $name
	 * @return \Iyoworks\FormBuilder\FormRenderer
	 * @throws Exceptions\RendererNotFound
	 */
	public function getRenderer($name)
	{
		if (!isset($this->renderers[$name]))
		{
			throw new RendererNotFound($name);
		}
		if (!$this->resolvedRenderers[$name])
		{
			$callback = $this->renderers[$name];
			$this->renderers[$name] = call_user_func($callback);
		}
		return $this->renderers[$name];
	}

	/**
	 * @param BinderInterface $binderInterface
	 */
	public function addDefaultBinder(BinderInterface $binderInterface)
	{
		$this->defaultBinders[] = $binderInterface;
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

	/**
	 * @return Form
	 */
	protected function _form()
	{
		if (is_null($this->_form))
		{
			$this->_form = $this->form();
			$this->_form->setProperty('allowFieldOverwrite', true);
		}
		return $this->_form;
	}

	/**
	 * @param $fieldType
	 * @param $arguments
	 * @return Field
	 */
	public function __call($fieldType, $arguments)
	{
		$method = 'add' . Str::studly($fieldType);
		if (method_exists($this, $method))
		{
			return call_user_func([$this->_form(), $method], $arguments);
		}
		return $this->_form()->addDynamicField($fieldType, $arguments);
	}
}