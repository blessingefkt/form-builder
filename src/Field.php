<?php namespace Iyoworks\FormBuilder;

/**
 * Class Field
 * @method Field slug()          slug(string $slug)
 * @method Field description()   description(string $description)
 * @method Field container()     container(mixed $element)
 * @method Field label()         label(string $label)
 * @method Field options()       options(array $options)
 * @method Field baseNames()     baseNames(array $baseNames)
 * @method Field row()           row(\Closure $row, string $rowId)
 * @method Field rowSize()       rowSize(int $rowSize)
 * @method Field skip()          skip(boolean $skip)
 * @property string $slug
 * @property string $type
 * @property string|mixed $value
 * @property Element $container
 * @property string $description
 * @property string $label
 * @property array $options
 * @property array $baseNames
 * @property string $row
 * @property int $rowSize
 * @property bool $skip     if true, the field will be skipped when the form renders all fields
 */
class Field extends Element {
	const RAW_FIELD_TYPE = 'raw';
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
		'skip' => false,
		'baseNames' => [],
	);
	/**
	 * @var Form
	 */
	private $form;

	/**
	 * Creates a new form field.
	 *
	 * @param Form $form
	 * @param array $slug
	 * @param string $type
	 * @param string|null $value
	 * @param array $attributes
	 * @param array $properties
	 */
	public function __construct(Form $form, $slug, $type, $value = null, array $attributes = [], array $properties = [])
	{
		parent::__construct($attributes, $properties);
		$this->form = $form;
		$this->slug = $slug;
		$this->type($type);
		$this->value($value);
		$container = new Element();
		$container->setProperty('tag', 'div');
		$this->setProperty('container', $container);
	}

	/**
	 * @param string $key
	 * @param null $default
	 * @return mixed|string
	 */
	public function get($key, $default = null)
	{
		if ($key == 'slug') return $this->slug;
		return parent::get($key, $default);
	}

	/**
	 * Set the field type.
	 *
	 * @param  string $type
	 *
	 * @return \Iyoworks\FormBuilder\Field
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
	 * @return \Iyoworks\FormBuilder\Field
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
			$values = (array)$value;
			foreach ($values as $value)
			{
				if ($onTop)
				{
					array_unshift($names, $value);
				}
				else
				{
					array_push($names, $value);
				}
			}
			$this->setProperty('baseNames', $names);
		}
		return $this;
	}

	public function getAttr($key, $default = null)
	{
		if ($key == 'name')
		{
			return $this->makeName(parent::getAttr($key, $default));
		}
		return parent::getAttr($key, $default);
	}


	/**
	 * @param $value
	 * @return string
	 */
	protected function makeName($value)
	{
		if ($value === false)
		{
			return null;
		}
		elseif (is_null($value))
		{
			$value = $this->slug;
		}
		$nameArray = explode('.', $value);
		$baseNames = $this->getProperty('baseNames');
		if ($baseNames === false) $baseNames = [];
		$nameArray = array_merge($baseNames, $nameArray);
		return $value = $this->makeFieldName($value, $nameArray, $this->multiple);
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
		$baseNames = (array)$baseNames;
		$baseNames[] = $name;
		$name = null;
		foreach ($baseNames as $_name)
		{
			if (!$name)
			{
				if ($_name === false)
				{
					$name = -1;
				}
				else
				{
					$name = $_name;
				}
			}
			elseif ($name == -1)
			{
				$name = $_name;
				break;
			}
			else
			{
				$name .= '[' . $_name . ']';
			}
		}

		if ($multiple) return $name . '[]';
		return $name;
	}

	/**
	 * Render the field
	 *
	 * @return string
	 */
	public function render()
	{
		return $this->form->renderField($this);
	}

	/**
	 * @return string
	 */
	public function __toString()
	{
		return $this->render();
	}
}