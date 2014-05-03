<?php namespace Flynsarmy\FormBuilder;

interface FormRenderer
{
    /**
     * @param Field $field
     * @return string
     */
    public function field(Field $field);

    /**
     * @param Element $row
     * @return string
     */
    public function rowOpen(Element $row);

    /**
     * @param Element $row
     * @return string
     */
    public function rowClose(Element $row);

    /**
     * @param Form $form
     * @return string
     */
    public function formOpen(Form $form);

    /**
     * @param Form $form
     * @return string
     */
    public function formClose(Form $form);

    /**
     * Add binders to the form
     * @param Form $form
     */
    public function setFormBinders(Form $form);
}