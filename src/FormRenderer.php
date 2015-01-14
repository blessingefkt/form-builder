<?php namespace Iyoworks\FormBuilder;

interface FormRenderer {
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
	 * @param $type
	 * @return bool
	 */
	public function isValidType($type);
}