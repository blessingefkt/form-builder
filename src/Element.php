<?php namespace Iyoworks\FormBuilder;


/**
 * Class Element
 */
class Element extends \Iyoworks\Html\Element {

	/**
	 * @return string
	 */
	public function dotName()
	{
		return $this->convertArraySyntaxToDotSyntax($this->name);
	}

	/**
	 * @param $str
	 * @return string
	 */
	protected function convertArraySyntaxToDotSyntax($str)
	{
		return trim(str_replace(['[', ']'], ['.', ''], $str), '.');
	}
}
