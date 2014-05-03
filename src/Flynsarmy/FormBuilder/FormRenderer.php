<?php namespace Flynsarmy\FormBuilder;

interface FormRenderer
{
    /**
     * @param Field $field
     * @return string
     */
    public function renderField(Field $field);

    /**
     * @param Form $form
     * @return string
     */
    public function renderFormOpen(Form $form);

    /**
     * @param Form $form
     * @return string
     */
    public function renderFormClose(Form $form);
}