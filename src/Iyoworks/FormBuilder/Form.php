<?php namespace Iyoworks\FormBuilder;

use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Iyoworks\FormBuilder\Exceptions\FieldAlreadyExists;
use Iyoworks\FormBuilder\Exceptions\FieldNotFound;
use Iyoworks\FormBuilder\Exceptions\UnknownType;
use Iyoworks\FormBuilder\Helpers\ArrayHelper;

/**
 * Class Form
 * @method $this model()           model(stdClass $value)
 * @method $this action()         action(string $value)
 * @method $this actionType()      actionType(string $value)
 * @method $this fieldNames()      fieldNames(array $value)
 * @method $this rendererName()   rendererName(string $value)
 * @method $this autoLabels()      autoLabels(bool $value)
 * @method $this skipAutoLabel()      autoLabels(array $value)
 * @method $this allowFieldOverwrite()   allowFieldOverwrite(bool $value)
 * @property bool $autoLabels
 * @property \Illuminate\Database\Eloquent\Model|\stdClass $model
 * @property string|array $action
 * @property string $actionType
 * @property array $fieldNames
 * @property array $skipAutoLabel
 * @property string $rendererName
 * @property bool $allowFieldOverwrite
 */
class Form extends Element {
	use Traits\Bindable;

	/**
	 * @var FormBuilderManager
	 */
	private $manager;
	/**
	 * @var FormRenderer
	 */
	protected $_renderer;
	/**
	 * @var array|Field[]
	 */
	protected $fields = [], $buffers = [];
	/**
	 * @var array|Element[]
	 */
	protected $rows = [];
	/**
	 * @var array|string[]
	 */
	protected $positions = [];

	protected $properties = array(
		 'skipAutoLabel' => ['hidden', 'submit', 'button', Field::RAW_FIELD_TYPE],
		 'autoLabels' => true,
		 'model' => null,
		 'slugChar' => '_',
		 'action' => [],
		 'actionType' => 'url',
		 'fieldNames' => [],
		 'rendererName' => null,
		 'allowFieldOverwrite' => false
	);

	/**
	 * @param FormBuilderManager $manager
	 * @param string $rendererName
	 * @param array $attributes
	 * @param array $properties
	 */
	public function __construct(FormBuilderManager $manager, $rendererName,
	                            array $attributes = [], array $properties = [])
	{
		parent::__construct($attributes, $properties);
		$this->manager = $manager;
		$this->rendererName = $rendererName;
		$this->method('post');
	}

	/**
	 * Add a addRow of field to the form
	 * @param \Closure $closure the form is passed into the closure,
	 *                              any fields created in the closure will be added to the addRow
	 * @param array|string $rowId defaults to a random string
	 * @return Element              the element object of the addRow
	 */
	public function addRow(\Closure $closure, $rowId = null)
	{
		if (is_null($rowId)) $rowId = 'row-' . Str::random(8);
		$row = new Element(['id' => $rowId]);
		$row->addClass('field-row');
		$this->buffer(['row' => $rowId], $closure, [$row]);
		return $this->rows[$rowId] = $row;
	}

	/**
	 * @param array $properties
	 * @param callable $callable
	 * @param array $passIn
	 * @return $this
	 */
	public function buffer(array $properties, callable $callable, array $passIn = [])
	{
		$bufferId = count($this->buffers) + 1;
		$this->buffers[$bufferId] = [];
		array_unshift($passIn, $this);
		call_user_func_array($callable, $passIn);
		$fields = array_pull($this->buffers, $bufferId, []);
		foreach ($fields as $field)
		{
			$field->setProperties($properties);
		}
		return $this;
	}

	/**
	 * Add a name to prepend to every field's name.
	 * EX: <input name='$name[some_field]'>, <input name='$name[another_name][some_field]'>
	 * @param string|dynamic $name
	 * @return $this
	 */
	public function addFieldName($name)
	{
		$args = func_get_args();
		$args = array_reverse($args);
		$this->fieldNames = array_merge($this->fieldNames, $args);
		return $this;
	}

	/**
	 * @param string $label
	 * @param null $value
	 * @param string $slug
	 * @return Field
	 */
	public function addSubmit($label = 'Submit', $value = null, $slug = 'submit')
	{
		$field = $this->add($slug, 'submit')->label($label)->value($value);
		return $field;
	}

	/**
	 * @param string $slug
	 * @param string $value
	 * @return Field
	 */
	public function addHidden($slug, $value = null)
	{
		$field = $this->add($slug, 'hidden')->value($value)->container(false);
		return $field;
	}

	/**
	 * @param string $value
	 * @param string $slug
	 * @return Field
	 */
	public function addRaw($slug, $value)
	{
		$field = $this->add($slug, Field::RAW_FIELD_TYPE)->value($value)->container(false);
		return $field;
	}

	/**
	 * Set the form's action attribute to resolve to a named route
	 * @param $action
	 * @return $this
	 */
	public function route($action)
	{
		return $this->url($action, 'route');
	}

	/**
	 * Set the form's action attribute to resolve to a controller action
	 * @param $action
	 * @return $this
	 */
	public function action($action)
	{
		return $this->url($action, 'action');
	}

	/**
	 * Set the form's action attribute
	 * @param $action
	 * @param string $actionType examples: url,action,route
	 * @return $this
	 */
	public function url($action, $actionType = 'url')
	{
		$this->action = $action;
		$this->actionType = $actionType;
		return $this;
	}

	/**
	 * @param $value
	 * @return $this
	 */
	public function method($value)
	{
		$this->setAttr('method', $value);
		$this->setProperty('method', $value);
		return $this;
	}

	/**
	 * Add a callback that triggers before the form is rendered
	 * @param callable $callback
	 * @return $this
	 */
	public function beforeForm(\Closure $callback)
	{
		$this->bind('beforeForm', $callback);
		return $this;
	}

	/**
	 * Add a callback that triggers after the form is rendered
	 * @param callable $callback
	 * @return $this
	 */
	public function afterForm(\Closure $callback)
	{
		$this->bind('afterForm', $callback);
		return $this;
	}

	/**
	 * Add a callback that triggers before every field is rendered
	 * @param callable $callback
	 * @return $this
	 */
	public function beforeField(\Closure $callback)
	{
		$this->bind('beforeField', $callback);
		return $this;
	}

	/**
	 * Add a callback that triggers after every field is rendered
	 * @param callable $callback
	 * @return $this
	 */
	public function afterField(\Closure $callback)
	{
		$this->bind('afterField', $callback);
		return $this;
	}

	/**
	 * Add a new field to the form
	 *
	 * @param  string $slug Unique identifier for this field
	 * @param  string $type Type of field
	 *
	 * @throws Exceptions\FieldAlreadyExists
	 * @return \Iyoworks\FormBuilder\Field
	 */
	public function add($slug, $type = null)
	{
		return $this->addAtPosition(sizeof($this->fields), $slug, $type);
	}

	/**
	 * Add a new field to the form
	 *
	 * @param  string $existingId slug of field to insert before
	 * @param  string $slug Unique identifier for this field
	 * @param  string $type Type of field
	 *
	 * @throws Exceptions\FieldNotFound
	 * @throws Exceptions\FieldAlreadyExists
	 * @return \Iyoworks\FormBuilder\Field
	 */
	public function addBefore($existingId, $slug, $type = null)
	{
		$keyPosition = array_search($existingId, $this->positions);
		if ($keyPosition == false)
		{
			throw new FieldNotFound("Field with slug '$existingId' does't exist.");
		}

		return $this->addAtPosition($keyPosition, $slug, $type);
	}

	/**
	 * Add a new field to the form
	 *
	 * @param  string $existingId slug of field to insert after
	 * @param  string $slug Unique identifier for this field
	 * @param  string $type Type of field
	 *
	 * @throws Exceptions\FieldNotFound
	 * @throws Exceptions\FieldAlreadyExists
	 * @return \Iyoworks\FormBuilder\Field
	 *
	 */
	public function addAfter($existingId, $slug, $type = null)
	{
		$keyPosition = array_search($existingId, $this->positions);
		if ($keyPosition == false)
		{
			throw new FieldNotFound("Field with slug '$existingId' does't exist.");
		}

		return $this->addAtPosition(++$keyPosition, $slug, $type);
	}

	/**
	 * Add a new field to the form at a given position
	 * binders: newField, new{Fieldtype}Field
	 *
	 * @param  integer $position Array index position to add the field
	 * @param  string $slug Unique identifier for this field
	 * @param  string $type Type of field, defaults to 'text'
	 *
	 * @throws Exceptions\UnknownType
	 * @throws Exceptions\FieldAlreadyExists
	 * @return \Iyoworks\FormBuilder\Field
	 */
	protected function addAtPosition($position, $slug, $type = null)
	{
		if (isset($this->fields[$slug]) && !$this->allowFieldOverwrite)
		{
			throw new FieldAlreadyExists("Field with slug '$slug' has already been added to this form.");
		}

		if (is_null($type)) $type = 'text';
		if (!$this->isValidType($type))
		{
			throw new UnknownType($type);
		}
		$field = new Field($this, $slug, $type);
		$field->row = 'row-' . count($this->fields) * rand(1, 10) . count($this->fields);
		if ($this->autoLabels && !in_array($field->type, $this->skipAutoLabel) && is_null($field->label))
		{
			$field->label = Str::title(str_replace('_', ' ', $field->slug));
		}
		$this->fire('newField', $field);
		$this->fire('new' . Str::studly($field->type) . 'Field', $field);
		if (!empty($this->buffers))
		{
			foreach ($this->buffers as $k => $buffer)
			{
				$buffer[] = $field;
				$this->buffers[$k] = $buffer;
			}
		}
		$this->fields[$field->slug] = $field;
		$this->positions = ArrayHelper::insert($this->positions, [$position => $field->slug], $position);
		return $field;
	}

	/**
	 * Retrieve a field with given slug
	 *
	 * @param  string $slug Unique identifier for the field
	 *
	 * @throws Exceptions\FieldNotFound
	 * @return \Iyoworks\FormBuilder\Field
	 */
	public function getField($slug)
	{
		if (!$this->hasField($slug))
		{
			throw new FieldNotFound("Field with slug '$slug' does't exist.");
		}

		return $this->fields[$slug];
	}

	/**
	 * Alias for getField
	 * @param $slug
	 * @return Field
	 */
	public function find($slug)
	{
		return $this->getField($slug);
	}

	/**
	 * Determine if a field exists
	 *
	 * @param  string $slug Unique identifier for the field
	 * @return bool
	 */
	public function hasField($slug)
	{
		return isset($this->fields[$slug]);
	}

//	/**
//	 * @param string $slug
//	 * @param string|int $position slug of an existing field or an integer
//	 */
//	public function move($slug, $position)
//	{
//		if (is_int($position))
//		{
//			$pos = $position;
//		}
//		else
//		{
//			$pos = array_search($this->positions, $slug);
//				if ($pos == false)
//				{
//					throw new FieldNotFound($position);
//				}
//		}
//		ArrayHelper::insert($this->positions, [$position => $slug], $pos);
//	}

	/**
	 * Remove a field from the form by slug
	 *
	 * @param  string $slug Unique identifier for the field
	 *
	 * @throws Exceptions\FieldNotFound
	 * @return \Iyoworks\FormBuilder\Form
	 */
	public function remove($slug)
	{
		if (!isset($this->fields[$slug]))
		{
			throw new FieldNotFound("Field with slug '$slug' does't exist.");
		}

		unset($this->fields[$slug]);

		return $this;
	}

	/**
	 * Set the form's model
	 * @param $model
	 * @return $this
	 */
	public function model($model)
	{
		$this->model = $model;
		return $this;
	}

	/**
	 * Render to form's opening tag
	 * @param array $attributes
	 * @return string
	 */
	public function open(array $attributes = array())
	{
		$this->mergeAttributes($attributes);
		return $this->fire('beforeForm', $this) . $this->getRenderer()->formOpen($this);
	}

	/**
	 * Render the form's closing tag
	 * @return string
	 */
	public function close()
	{
		return $this->fire('afterForm', $this) . $this->getRenderer()->formClose($this);
	}

	/**
	 * Render the form, including the form's opening and closing tags
	 * @param array $options
	 * @return string
	 */
	public function html(array $options = [])
	{
		return $this->open($options) . $this->render() . $this->close();
	}

	/**
	 * Render the form's fields
	 *
	 * @return string
	 */
	public function render()
	{
		$output = '';
		// Render a rowless form
		if (sizeof($this->rows) == count($this->fields))
		{
			$output .= $this->renderFields($this->getOrderedFields());
		}
		else
		{
			$rows = $this->getFieldsByRow('_default');
			foreach ($rows as $rowId => $rowFields)
			{
				$row = array_pull($this->rows, $rowId, false);
				if ($row)
				{
					$output .= $this->renderRow($row, $rowFields);
				}
				else
				{
					$output .= $this->renderFields($rowFields);
				}
			}
			if (isset($fields['_default']))
			{
				$output .= $this->renderFields($fields['_default']);
			}
		}

		return $output;
	}

	/**
	 * Returns an array of fields grouped by row
	 * @param  string|null $default fields without a row will be assigned to this key
	 * @return array
	 * [
	 *   'rowId' => [$field, $field, ...],
	 *   ...
	 * ]
	 */
	public function getFieldsByRow($default = '_default')
	{
		$sorted = $this->getFieldsByProperty('row', $default);
		return $sorted;
	}

	/**
	 * Returns the field list broken up by a given setting.
	 *
	 * @param  string $property A field setting such as 'tab'. These will form
	 *                         the keys of the associative array returned.
	 * @param  string $default Default value to use if the setting doesn't exist
	 *                         for a field.
	 *
	 * @return array
	 * [
	 *   'foo' => [$field, $field, ...],
	 *   'bar' => [$field, $field, ...],
	 *   ...
	 * ]
	 */
	protected function getFieldsByProperty($property, $default = '')
	{
		$sorted = array();

		foreach ($this->getOrderedFields() as $field)
		{
			$field_property = $field->getProperty($property, $default);
			$sorted[$field_property][$field->slug] = $field;
		}

		return $sorted;
	}

	/**
	 * Render a list of fields.
	 * @param Element $row
	 * @param array|Field[] $fields
	 * @return string
	 */
	protected function renderRow($row, $fields)
	{
		$count = count($fields);
		$output = $this->fire('beforeRow', $row, $fields);
		$output .= $this->getRenderer()->rowOpen($row, $fields);
		$output .= $this->renderFields($fields, function ($field) use ($row, $count)
		{
			$field->setProperty('rowSize', $count);
		});
		$output .= $this->getRenderer()->rowClose($row, $fields);
		$output .= $this->fire('afterRow', $row, $fields);
		return $output;
	}

	/**
	 * Render a list of fields.
	 *
	 * @param  array|Field[] $fields
	 * @param callable $callable
	 * @return string
	 */
	protected function renderFields($fields, callable $callable = null)
	{
		$outputs = [];
		foreach ($this->getOrderedFields($fields) as $field)
		{
			if ($field->skip) continue;
			if ($callable) call_user_func($callable, $field);
			$outputs[] = $this->renderField($field);
		}
		return join("\n", $outputs);
	}

	/**
	 * Render a given field.
	 *
	 * @param  Field $field
	 *
	 * @return string
	 */
	public function renderField(Field $field)
	{
		$output = '';

		if ($this->fieldNames)
		{
			$field->addName($this->fieldNames, true);
		}

		$output .= $this->fire('beforeField', $this, $field);

		if ($field->type == Field::RAW_FIELD_TYPE)
		{
			$fieldHtml = $field->value;
		}
		elseif ($this->manager->isMacro($field->type))
		{
			$fieldHtml = $this->manager->callMacro($field->type, $field, $this->getRenderer());
		}
		else
		{
			$fieldHtml = $this->getRenderer()->field($field);
		}

		$output .= $fieldHtml;

		$output .= $this->fire('afterField', $this, $field);

		return $output;
	}

	/**
	 * @return FormRenderer
	 */
	public function getRenderer()
	{
		if (!$this->_renderer)
		{
			$this->_renderer = $this->manager->getRenderer($this->rendererName);
		}
		return $this->_renderer;
	}

	/**
	 * @param $type
	 * @return bool
	 */
	public function isValidType($type)
	{
		return $type == Field::RAW_FIELD_TYPE
		|| $this->manager->isMacro($type)
		|| $this->getRenderer()->isValidType($type);
	}

	/**
	 * @return \Illuminate\Support\Collection|\Iyoworks\FormBuilder\Element[]
	 */
	public function getRows()
	{
		return new Collection($this->rows);
	}

	/**
	 * @return \Illuminate\Support\Collection|\Iyoworks\FormBuilder\Field[]
	 */
	public function getFields()
	{
		return new Collection($this->fields);
	}

	/**
	 * @return array|string
	 */
	public function getAction()
	{
		return $this->action;
	}

	/**
	 * @return string
	 */
	public function getActionType()
	{
		return $this->actionType;
	}

	/**
	 * @return array
	 */
	public function getFieldNames()
	{
		return $this->fieldNames;
	}

	/**
	 * @return mixed
	 */
	public function getModel()
	{
		return $this->model;
	}

	/**
	 * @return array
	 */
	public function getFieldAttributeBuffer()
	{
		return $this->fieldAttributeBuffer;
	}

	/**
	 * @return array
	 */
	public function getFieldPropertyBuffer()
	{
		return $this->fieldPropertyBuffer;
	}

	/**
	 * @return string
	 */
	public function getRendererName()
	{
		return $this->rendererName;
	}

	/**
	 * @param bool $value
	 * @return $this
	 */
	public function autoLabels($value = true)
	{
		$this->autoLabels = (bool)$value;
		return $this;
	}

	/**
	 * @return boolean
	 */
	public function autoLabelsEnabled()
	{
		return $this->autoLabels;
	}

	/**
	 * Dynamically set properties and settings
	 * calling $form->add{Fieldtype}($slug [,$label]) will add a new field to the form
	 *
	 * @param  string $name Setting name
	 * @param  array $arguments Setting value(s)
	 *
	 * @return $this|\Iyoworks\FormBuilder\Field
	 */
	public function __call($name, $arguments)
	{
		if (preg_match("/add([A-Z][\w]+)(After|Before)([A-Z][\w]+)/", $name, $matched))
		{
			$type = lcfirst($matched[1]);
			$isBefore = $matched[2] == 'Before';
			$reference = lcfirst($matched[3]);
			return $this->addDynamicField($type, $arguments, $reference, $isBefore);
		}
		elseif (preg_match("/add([A-Z][\w]+)/", $name, $matched))
		{
			$type = lcfirst($matched[1]);
			return $this->addDynamicField($type, $arguments);
		}

		return parent::__call($name, $arguments);
	}

	/**
	 * @return string
	 */
	public function __toString()
	{
		return $this->render();
	}

	/**
	 * @param string $type
	 * @param array $arguments
	 * @param null $referenceField
	 * @param bool $before
	 * @return Field
	 */
	public function addDynamicField($type, $arguments, $referenceField = null, $before = false)
	{
		$slug = array_get($arguments, 0);
		if ($referenceField)
		{
			$referenceField = Str::lower(preg_replace('/([A-Z])/', "{$this->slugChar}\$1", $referenceField));
			if ($before)
				$field = $this->addBefore($referenceField, $slug, $type);
			else
				$field = $this->addAfter($referenceField, $slug, $type);
		}
		else
		{
			$field = $this->add($slug, $type);
		}
		$label = array_get($arguments, 1);
		if ($label)
		{
			$field->label($label);
		}
		return $field;
	}

	/**
	 * @param array|Field[] $fields
	 * @return array|Field[]
	 */
	public function getOrderedFields(array $fields = null)
	{
		if (!$fields) $fields = $this->fields;
		$keys = array_keys($fields);
		$positions = array_intersect($this->positions, $keys);
		if ($keys == $positions) return $fields;
		$_fields = [];
		foreach ($positions as $field)
		{
			$_fields[$field] = $this->fields[$field];
		}

		return $_fields;
	}
}