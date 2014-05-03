<?php namespace Flynsarmy\FormBuilder;

use Closure;
use Flynsarmy\FormBuilder\Exceptions\FieldAlreadyExists;
use Flynsarmy\FormBuilder\Exceptions\FieldNotFound;
use Flynsarmy\FormBuilder\Helpers\ArrayHelper;
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
     * @var array
     */
    protected $fields = [], $fieldNames = [];
    /**
     * @var string
     */
    protected $action, $actionType, $rendererName;

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

    public function bindClass(BinderInterface $binder)
    {
        $this->bind('beforeField', [$binder, 'beforeField']);
        $this->bind('afterField', [$binder, 'afterField']);
        $this->bind('afterForm', [$binder, 'afterForm']);
        $this->bind('beforeForm', [$binder, 'beforeForm']);
        return $this;
    }

    /**
     * @return FormRenderer
     */
    public function renderer()
    {
        if (!$this->_renderer)
        {
            $rendererCallback = $this->manager->getRenderer($this->rendererName);
            $this->_renderer =  call_user_func($rendererCallback, $this);
        }
        return $this->_renderer;
    }

    /**
     * Add a name to prepend to every field's name.
     * EX: <input name='$name[some_field]'>, <input name='$name[another_name][some_field]'>
     * @param string|dynamic $name
     * @return $this
     */
    public function addFieldName($name)
    {
        $this->fieldNames = array_merge($this->fieldNames, func_get_args());
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
     * @param  string $id Unique identifier for this field
     * @param  string $type Type of field
     *
     * @throws Exceptions\FieldAlreadyExists
     * @return \Flynsarmy\FormBuilder\Field
     */
    public function add($id, $type)
    {
        if ( isset($this->fields[$id]) )
            throw new FieldAlreadyExists("Field with id '$id' has already been added to this form.");

        return $this->addAtPosition(sizeof($this->fields), $id, $type);
    }

    /**
     * Add a new field to the form
     *
     * @param  string $existingId ID of field to insert before
     * @param  string $id Unique identifier for this field
     * @param  string $type Type of field
     *
     * @throws Exceptions\FieldNotFound
     * @throws Exceptions\FieldAlreadyExists
     * @return \Flynsarmy\FormBuilder\Field
     */
    public function addBefore($existingId, $id, $type)
    {
        $keyPosition = ArrayHelper::getKeyPosition($this->fields, $existingId);
        if ( $keyPosition == -1 )
            throw new FieldNotFound("Field with id '$existingId' does't exist.");

        if ( isset($this->fields[$id]) )
            throw new FieldAlreadyExists("Field with id '$id' has already been added to this form.");

        return $this->addAtPosition($keyPosition, $id, $type);
    }

    /**
     * Add a new field to the form
     *
     * @param  string $existingId ID of field to insert after
     * @param  string $id Unique identifier for this field
     * @param  string $type Type of field
     *
     * @throws Exceptions\FieldNotFound
     * @throws Exceptions\FieldAlreadyExists
     * @return \Flynsarmy\FormBuilder\Field
     *
     */
    public function addAfter($existingId, $id, $type)
    {
        $keyPosition = ArrayHelper::getKeyPosition($this->fields, $existingId);
        if ( $keyPosition == -1 )
            throw new FieldNotFound("Field with id '$existingId' does't exist.");

        if ( isset($this->fields[$id]) )
            throw new FieldAlreadyExists("Field with id '$id' has already been added to this form.");

        return $this->addAtPosition(++$keyPosition, $id, $type);
    }

    /**
     * Add a new field to the form at a given position
     *
     * @param  integer $position     Array index position to add the field
     * @param  string  $id           Unique identifier for this field
     * @param  string $type          Type of field
     *
     * @return \Flynsarmy\FormBuilder\Field
     */
    protected function addAtPosition($position, $id, $type)
    {
        $field = new Field($id, $type);
        $this->fields = ArrayHelper::insert($this->fields, [$id => $field], $position);
        return $field;
    }

    /**
     * Retrieve a field with given ID
     *
     * @param  string $id Unique identifier for the field
     *
     * @throws Exceptions\FieldNotFound
     * @return \Flynsarmy\FormBuilder\Field
     */
    public function get($id)
    {
        if ( !isset($this->fields[$id]) )
            throw new FieldNotFound("Field with id '$id' does't exist.");

        return $this->fields[$id];
    }

    /**
     * Remove a field from the form by ID
     *
     * @param  string $id Unique identifier for the field
     *
     * @throws Exceptions\FieldNotFound
     * @return \Flynsarmy\FormBuilder\Form
     */
    public function remove($id)
    {
        if ( !isset($this->fields[$id]) )
            throw new FieldNotFound("Field with id '$id' does't exist.");

        unset($this->fields[$id]);

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
        return $this->renderer()->renderFormOpen($this);
    }

    /**
     * Render the form's closing tag
     * @return string
     */
    public function close()
    {
        return $this->renderer()->renderFormClose($this);
    }

    /**
     * Render the form
     *
     * @return string
     */
    public function render()
    {
        $output = '';
        // Are we using a tabbed interface?
        $tabs = $this->getFieldsBySetting('tab', '');

        $output .= $this->fire('beforeForm', $this, $tabs);

        // Render a tabless form
        if ( sizeof($tabs) == 1 )
        {
            $output .= $this->renderFields($this->fields);
        }
        else
        {
            foreach ( $tabs as $name => $fields )
                $output .= $this->renderFields($fields);
        }

        $output .= $this->fire('afterForm', $this, $tabs);

        return $output;
    }

    /**
     * Returns the field list broken up by a given setting.
     *
     * @param  string $setting A field setting such as 'tab'. These will form
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
    protected function getFieldsBySetting($setting, $default='')
    {
        $sorted = array();

        foreach ( $this->fields as $field )
        {
            if ( isset($field->settings[$setting]) )
                $field_setting = $field->settings[$setting];
            else
                $field_setting = $default;

            $sorted[$field_setting][] = $field;
        }

        return $sorted;
    }

    /**
     * Render a list of fields.
     *
     * @param  array  $fields
     *
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
        $output .= $this->fire('beforeField', $this, $field);

        if ($this->fieldNames)
            $field->addName($this->fieldNames, true);

        if ($this->manager->isMacro($field->type))
            $output .= $this->manager->callMacro($field->type, $field, $this->render());
        else
            $output .= $this->renderer()->renderField($field);

        $output .= $this->fire('afterField', $this, $field);

        return $output;
    }

}