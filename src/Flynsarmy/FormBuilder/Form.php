<?php namespace Flynsarmy\FormBuilder;

use Closure;
use Flynsarmy\FormBuilder\Exceptions\FieldAlreadyExists;
use Flynsarmy\FormBuilder\Exceptions\FieldNotFound;
use Flynsarmy\FormBuilder\Helpers\ArrayHelper;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Class Form
 * @property string $model
 * @property string $actionType
 * @property string $action
 * @property array $fieldNames
 */
class Form extends Element
{
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
     * @var \stdClass
     */
    protected $model;
    /**
     * @var array|Field[]
     */
    protected $fields = [];
    /**
     * @var array|Element[]
     */
    protected $rows = [];
    /**
     * @var array
     */
    protected $fieldNames = [],
        $fieldPropertyBuffer = [],
        $fieldAttributeBuffer = [];
    /**
     * @var string
     */
    protected $action, $actionType, $rendererName;
    protected $enableAutoLabels = true;

    /**
     * @param FormBuilderManager $manager
     * @param string $rendererName
     * @param array $attributes
     */
    public function __construct(FormBuilderManager $manager, $rendererName, array $attributes = [])
    {
        parent::__construct($attributes);
        $this->manager = $manager;
        $this->rendererName = $rendererName;
    }

    /**
     * Add a addRow of field to the form
     * @param \Closure $closure     the form is passed into the closure,
     *                              any fields created in the closure will be added to the addRow
     * @param array|string $rowId   defaults to a random string
     * @return Element              the element object of the addRow
     */
    public function addRow(\Closure $closure, $rowId = null)
    {
        if (is_null($rowId)) $rowId = Str::random(8);
        $this->rows[$rowId] = new Element(['id' => 'row-'.$rowId]);
        $this->rows[$rowId]->addClass('field-row');
        $this->addBufferProperties(['row' => $rowId]);
        call_user_func($closure, $this);
        $this->clearBuffers();
        return $this->rows[$rowId];
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
     * @return \Flynsarmy\FormBuilder\Field
     */
    public function add($slug, $type)
    {
        if ( isset($this->fields[$slug]) )
            throw new FieldAlreadyExists("Field with id '$slug' has already been added to this form.");

        return $this->addAtPosition(sizeof($this->fields), $slug, $type);
    }

    /**
     * Add a new field to the form
     *
     * @param  string $existingId ID of field to insert before
     * @param  string $slug Unique identifier for this field
     * @param  string $type Type of field
     *
     * @throws Exceptions\FieldNotFound
     * @throws Exceptions\FieldAlreadyExists
     * @return \Flynsarmy\FormBuilder\Field
     */
    public function addBefore($existingId, $slug, $type)
    {
        $keyPosition = ArrayHelper::getKeyPosition($this->fields, $existingId);
        if ( $keyPosition == -1 )
            throw new FieldNotFound("Field with id '$existingId' does't exist.");

        if ( isset($this->fields[$slug]) )
            throw new FieldAlreadyExists("Field with id '$slug' has already been added to this form.");

        return $this->addAtPosition($keyPosition, $slug, $type);
    }

    /**
     * Add a new field to the form
     *
     * @param  string $existingId ID of field to insert after
     * @param  string $slug Unique identifier for this field
     * @param  string $type Type of field
     *
     * @throws Exceptions\FieldNotFound
     * @throws Exceptions\FieldAlreadyExists
     * @return \Flynsarmy\FormBuilder\Field
     *
     */
    public function addAfter($existingId, $slug, $type)
    {
        $keyPosition = ArrayHelper::getKeyPosition($this->fields, $existingId);
        if ( $keyPosition == -1 )
            throw new FieldNotFound("Field with id '$existingId' does't exist.");

        if ( isset($this->fields[$slug]) )
            throw new FieldAlreadyExists("Field with id '$slug' has already been added to this form.");

        return $this->addAtPosition(++$keyPosition, $slug, $type);
    }

    /**
     * Add a new field to the form at a given position
     * binders: newField, new{Fieldtype}Field
     *
     * @param  integer $position     Array index position to add the field
     * @param  string  $slug           Unique identifier for this field
     * @param  string $type          Type of field
     *
     * @return \Flynsarmy\FormBuilder\Field
     */
    protected function addAtPosition($position, $slug, $type)
    {
        $field = new Field($slug, $type);
        $field->mergeAttributes($this->fieldAttributeBuffer);
        $field->appendProperties($this->fieldPropertyBuffer);
        $this->fire('newField', $field);
        $this->fire('new'.Str::studly($field->type).'Field', $field);
        $this->fields = ArrayHelper::insert($this->fields, [$field->slug => $field], $position);
        return $field;
    }

    /**
     * Retrieve a field with given ID
     *
     * @param  string $slug Unique identifier for the field
     *
     * @throws Exceptions\FieldNotFound
     * @return \Flynsarmy\FormBuilder\Field
     */
    public function get($slug)
    {
        if ( !isset($this->fields[$slug]) )
            throw new FieldNotFound("Field with id '$slug' does't exist.");

        return $this->fields[$slug];
    }

    /**
     * Remove a field from the form by ID
     *
     * @param  string $slug Unique identifier for the field
     *
     * @throws Exceptions\FieldNotFound
     * @return \Flynsarmy\FormBuilder\Form
     */
    public function remove($slug)
    {
        if ( !isset($this->fields[$slug]) )
            throw new FieldNotFound("Field with id '$slug' does't exist.");

        unset($this->fields[$slug]);

        return $this;
    }

    /**
     * Set the form's model and render the opening tag
     * @param $model
     * @param array $attributes
     * @return string
     */
    public function model($model, array $attributes = array())
    {
        $this->model = $model;
        return $this->open($attributes);
    }

    /**
     * Render to form's opening tag
     * @param array $attributes
     * @return string
     */
    public function open(array $attributes = array())
    {
        $this->mergeAttributes($attributes);
        return $this->getRenderer()->formOpen($this);
    }

    /**
     * Render the form's closing tag
     * @return string
     */
    public function close()
    {
        return $this->getRenderer()->formClose($this);
    }

    /**
     * Render the form
     *
     * @return string
     */
    public function render()
    {
        $output = '';

        $output .= $this->fire('beforeForm', $this);

        // Render a rowless form
        if ( sizeof($this->rows) == 0 )
        {
            $output .= $this->renderFields($this->fields);
        }
        else
        {
            $fields = $this->getFieldsByRow('_default');
            foreach ( $this->rows as $rowId => $row )
            {
                $rowFields = array_pull($fields, $rowId, []);
                if (!empty($rowFields))
                    $output .= $this->renderRow($row, $rowFields);
            }
            if (isset($fields['_default']))
            {
                $output .= $this->renderFields($fields['_default']);
            }
        }

        $output .= $this->fire('afterForm', $this);

        return $output;
    }

    /**
     * Returns an array of fields grouped by row
     * @param  string|null $default  fields without a row will be assigned to this key
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
    protected function getFieldsByProperty($property, $default='')
    {
        $sorted = array();

        foreach ( $this->fields as $field )
        {
            $field_property = $field->getProperty($property, $default);
            $sorted[$field_property][] = $field;
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
        foreach ( $fields as $field )
        {
            $field->setProperty('rowSize', $count);
            $output .= $this->renderField($field);
        }
        $output .= $this->getRenderer()->rowClose($row, $fields);
        $output .= $this->fire('afterRow', $row, $fields);
        return $output;
    }

    /**
     * Render a list of fields.
     *
     * @param  array $fields
     * @return string
     */
    protected function renderFields($fields)
    {
        $output = '';

        foreach ( $fields as $field )
            $output .= $this->renderField($field);

        return $output;
    }

    /**
     * Render a given field.
     *
     * @param  Field  $field
     *
     * @return string
     */
    public  function renderField(Field $field)
    {
        $output = '';

        if ($this->enableAutoLabels && !$field->label)
            $field->label = Str::title($field->slug);
        if ($this->fieldNames)
            $field->addName($this->fieldNames, true);

        $output .= $this->fire('beforeField', $this, $field);

        if ($this->manager->isMacro($field->type))
            $fieldHtml = $this->manager->callMacro($field->type, $field, $this->render());
        else
            $fieldHtml = $this->getRenderer()->field($field);

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
            $this->_renderer->setFormBinders($this);
        }
        return $this->_renderer;
    }

    /**
     * Append attributes to the attribute buffer
     * @param array $attributes
     * @return $this
     */
    public function addBufferAttributes(array $attributes = [])
    {
        $this->fieldAttributeBuffer = array_merge_recursive($this->fieldAttributeBuffer, $attributes);
        return $this;
    }

    /**
     * Append properties to the property buffer
     * @param array $properties
     * @return $this
     */
    public function addBufferProperties(array $properties = [])
    {
        $this->fieldPropertyBuffer = array_merge($this->fieldPropertyBuffer, $properties);
        return $this;
    }

    /**
     * Set field property and attribute buffers, overwriting any existing values
     * @param array $properties
     * @param array $attributes
     * @return $this
     */
    public function setBuffers(array $properties, array $attributes)
    {
        $this->fieldPropertyBuffer = $properties;
        $this->fieldAttributeBuffer = $attributes;
        return $this;
    }

    /**
     * Clear field property and attribute buffers
     * @return $this
     */
    public function clearBuffers()
    {
        $this->fieldPropertyBuffer = [];
        $this->fieldAttributeBuffer = [];
        return $this;
    }

    /**
     * @param mixed $model
     */
    public function setModel($model)
    {
        $this->model = $model;
        return $this;
    }

    /**
     * @return \Illuminate\Support\Collection|\Flynsarmy\FormBuilder\Element[]
     */
    public function getRows()
    {
        return new Collection($this->rows);
    }

    /**
     * @return \Illuminate\Support\Collection|\Flynsarmy\FormBuilder\Field[]
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
        $this->enableAutoLabels = (bool) $value;
        return $this;
    }

    /**
     * @return boolean
     */
    public function autoLabelsEnabled()
    {
        return $this->enableAutoLabels;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->render();
    }
}