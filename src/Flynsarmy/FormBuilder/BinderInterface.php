<?php namespace Flynsarmy\FormBuilder;


interface BinderInterface {

    /**
     * Add a callback that triggers before the form is rendered
     * @param Form $form
     * @return mixed
     */
    public function beforeForm(Form $form);

    /**
     * Add a callback that triggers after the form is rendered
     * @param Form $form
     * @return mixed
     */
    public function afterForm(Form $form);

    /**
     * Add a callback that triggers before every field is rendered
     * @param Form $form
     * @param Field $field
     * @return mixed
     */
    public function beforeField(Form $form, Field $field);

    /**
     * Add a callback that triggers after every field is rendered
     * @param Form $form
     * @param Field $field
     * @return mixed
     */
    public function afterField(Form $form, Field $field);


    /**
     * Add a callback that triggers before every addRow
     * @param Element $row
     * @param array $fields|Field[]
     * @return string
     */
    public function beforeRow(Element $row, array $fields);

    /**
     * Add a callback that triggers after every addRow
     * @param Element $row
     * @param array $fields|Field[]
     * @return string
     */
    public function afterRow(Element $row, array $fields);

} 