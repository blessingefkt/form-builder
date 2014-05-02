<?php namespace Flynsarmy\FormBuilder;

use Closure;
use Flynsarmy\FormBuilder\Field;
use Flynsarmy\FormBuilder\Exceptions\FieldAlreadyExists;
use Flynsarmy\FormBuilder\Exceptions\FieldNotFound;
use Flynsarmy\FormBuilder\Helpers\ArrayHelper;

class Form
{
    use Traits\Bindable;

    protected $fields = array();
    protected $action;
    protected $actionType;
    protected $model;

    /**
     * Set the form's model and render the opening tag
     * @param $model
     * @param array $options
     * @return mixed
     */
    public function model($model, array $options = array())
    {
        $this->model = $model;
        return $this->open($options);
    }

    /**
     * Render to form's opening tag
     * @param array $options
     * @return string
     */
    public function open(array $options = array())
    {
        if ($this->actionType)
            $options[$this->actionType] = $this->action;

        if ($this->model) return Element::getFormBuilder()->model($this->model, $options);
        return Element::getFormBuilder()->open($options);
    }

    /**
     * Render the form's closing tag
     * @return string
     */
    public function close()
    {
        return Element::getFormBuilder()->close();
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
     * @param string $actionType  supported options: url,action,route
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
     * @param  string  $id        Unique identifier for this field
     * @param  Closure $callback  Optional closure accepting a Field object
     *
     * @return mixed   Instance of Form if callback is specified, else instance of Field
     */
    public function add($id, $callback = null)
    {
        if ( isset($this->fields[$id]) )
            throw new FieldAlreadyExists("Field with id '$id' has already been added to this form.");

        return $this->addAtPosition(sizeof($this->fields), $id, $callback);
    }

    /**
     * Add a new field to the form
     *
     * @param  string  $existingId   ID of field to insert before
     * @param  string  $id           Unique identifier for this field
     * @param  Closure $callback     Optional closure accepting a Field object
     *
     * @return mixed                 Instance of Form if callback is specified,
     *                               else instance of Field
     */
    public function addBefore($existingId, $id, $callback = null)
    {
        $keyPosition = ArrayHelper::getKeyPosition($this->fields, $existingId);
        if ( $keyPosition == -1 )
            throw new FieldNotFound("Field with id '$existingId' does't exist.");

        if ( isset($this->fields[$id]) )
            throw new FieldAlreadyExists("Field with id '$id' has already been added to this form.");

        return $this->addAtPosition($keyPosition, $id, $callback);
    }

    /**
     * Add a new field to the form
     *
     * @param  string  $existingId   ID of field to insert after
     * @param  string  $id           Unique identifier for this field
     * @param  Closure $callback     Optional closure accepting a Field object
     *
     * @return mixed                 Instance of Form if callback is specified,
     *                               else instance of Field
     */
    public function addAfter($existingId, $id, $callback = null)
    {
        $keyPosition = ArrayHelper::getKeyPosition($this->fields, $existingId);
        if ( $keyPosition == -1 )
            throw new FieldNotFound("Field with id '$existingId' does't exist.");

        if ( isset($this->fields[$id]) )
            throw new FieldAlreadyExists("Field with id '$id' has already been added to this form.");

        return $this->addAtPosition(++$keyPosition, $id, $callback);
    }

    /**
     * Add a new field to the form at a given position
     *
     * @param  integer $position     Array index position to add the field
     * @param  string  $id           Unique identifier for this field
     * @param  Closure $callback     Optional closure accepting a Field object
     *
     * @return mixed                 Instance of Form if callback is specified,
     *                               else instance of Field
     */
    protected function addAtPosition($position, $id, $callback = null)
    {
        $field = new Field($id);
        $this->fields = ArrayHelper::insert($this->fields, [$id => $field], $position);

        if ( $callback instanceof Closure )
        {
            call_user_func($callback, $field);
            return $this;
        }

        return $field;
    }

    /**
     * Retrieve a field with given ID
     *
     * @param  string $id     Unique identifier for the field
     *
     * @return Flynsarmy\FormBuilder\Field
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
     * @param  string $id     Unique identifier for the field
     *
     * @return Flynsarmy\FormBuilder\Form
     */
    public function remove($id)
    {
        if ( !isset($this->fields[$id]) )
            throw new FieldNotFound("Field with id '$id' does't exist.");

        unset($this->fields[$id]);

        return $this;
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

        $output .= $this->fire('beforeForm', $form, $tabs);

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

        $output .= $this->fire('afterForm', $form, $tabs);

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
    protected function renderField(Field $field)
    {
        $output = '';
        $output .= $this->fire('beforeField', $form, $field);
        $output .= $field->render();
        $output .= $this->fire('afterField', $form, $field);

        return $output;
    }
}