<?php namespace Flynsarmy\FormBuilder;


use Flynsarmy\FormBuilder\Traits\Bindable;
use Illuminate\Html\FormBuilder as Builder;
use Illuminate\Html\HtmlBuilder;

class LaravelFormRenderer implements FormRenderer
{
    /**
     * @var Builder
     */
    protected $builder;
    /**
     * @var \Illuminate\Html\HtmlBuilder
     */
    private $htmlBuilder;

    public function __construct(Builder $builder, HtmlBuilder $htmlBuilder)
    {
        $this->builder = $builder;
        $this->htmlBuilder = $htmlBuilder;
    }

    /**
     * Add binders to the form
     * @param Form $form
     */
    public function setFormBinders(Form $form)
    {

    }


    /**
     * @param Form $form
     * @return mixed
     */
    public function formOpen(Form $form)
    {
        $options = $form->getAttributes();
        $options[$form->getActionType()] = $form->getAction();
        if ($model = $form->getModel())
            return $this->builder->model($model, $options);
        return $this->builder->open($options);
    }

    /**
     * @param Form $form
     * @return string
     */
    public function formClose(Form $form)
    {
        return $this->builder->close();
    }

    /**
     * @param Element $row
     * @return string
     */
    public function rowOpen(Element $row)
    {
        $_atts = $this->htmlBuilder->attributes($row->getAttributes());
        return '<div'.$_atts.'>';
    }

    /**
     * @param Element $row
     * @return string
     */
    public function rowClose(Element $row)
    {
        return'</div>';
    }


    /**
     * Render a given field.
     *
     * @param  Field  $field
     *
     * @return string
     */
    public  function field(Field $field)
    {
        if (method_exists($this, $field->type))
            return $this->{$field->type}($field);
        else
            return $this->input($field);
    }

    /**
     * @return string
     */
    public function token()
    {
        return $this->builder->token();
    }

    /**
     * @param Field $field
     * @return string
     */
    public function label(Field $field)
    {
        return $this->builder->label($field->name, $field->value, $field->getAttributes());
    }

    /**
     * @param Field $field
     * @return string
     */
    public function input(Field $field)
    {
        return $this->builder->input($field->type, $field->name, $field->value, $field->getAttributes());
    }

    /**
     * @param Field $field
     * @return string
     */
    public function text(Field $field)
    {
        return $this->builder->text($field->name, $field->value, $field->getAttributes());
    }

    /**
     * @param Field $field
     * @return string
     */
    public function password(Field $field)
    {
        return $this->builder->password($field->name, $field->getAttributes());
    }

    /**
     * @param Field $field
     * @return string
     */
    public function hidden(Field $field)
    {
        return $this->builder->hidden($field->name, $field->value, $field->getAttributes());
    }

    /**
     * @param Field $field
     * @return string
     */
    public function email(Field $field)
    {
        return $this->builder->email($field->name, $field->value, $field->getAttributes());
    }

    /**
     * @param Field $field
     * @return string
     */
    public function url(Field $field)
    {
        return $this->builder->url($field->name, $field->value, $field->getAttributes());
    }

    /**
     * @param Field $field
     * @return string
     */
    public function file(Field $field)
    {
        return $this->builder->file($field->name, $field->getAttributes());
    }

    /**
     * @param Field $field
     * @return string
     */
    public function textarea(Field $field)
    {
        return $this->builder->textarea($field->name, $field->value, $field->getAttributes());
    }

    /**
     * @param Field $field
     * @return string
     */
    public function select(Field $field)
    {
        return $this->builder->select($field->name, $field->options ?: [], $field->selected, $field->getAttributes());
    }

    /**
     * @param Field $field
     * @return string
     */
    public function selectRange(Field $field)
    {
        return $this->builder->selectRange($field->name, $field->begin, $field->end, $field->selected, $field->getAttributes());
    }

    /**
     * @param Field $field
     * @return string
     */
    public function selectYear(Field $field)
    {
        return $this->selectRange($field);
    }

    /**
     * @param Field $field
     * @return string
     */
    public function selectMonth(Field $field)
    {
        return $this->builder->selectMonth($field->name, $field->selected, $field->getAttributes());
    }

    /**
     * @param Field $field
     * @return string
     */
    public function checkbox(Field $field)
    {
        return $this->builder->checkbox($field->name, $field->value, $field->checked, $field->getAttributes());
    }

    /**
     * @param Field $field
     * @return string
     */
    public function radio(Field $field)
    {
        return $this->builder->radio($field->name, $field->value,$field->checked, $field->getAttributes());
    }

    /**
     * @param Field $field
     * @return string
     */
    public function reset(Field $field)
    {
        return $this->builder->reset($field->value, $field->getAttributes());
    }

    /**
     * @param Field $field
     * @return string
     */
    public function image(Field $field)
    {
        return $this->builder->image($field->url, $field->name, $field->getAttributes());
    }

    /**
     * @param Field $field
     * @return string
     */
    public function submit(Field $field)
    {
        return $this->builder->submit($field->value, $field->getAttributes());
    }

    /**
     * @param Field $field
     * @return string
     */
    public function button(Field $field)
    {
        return $this->builder->button($field->value, $field->getAttributes());
    }

} 