<?php

class Jp7_InterAdmin_Soap_Options {
	/**
	 * @var string
	 * @nillable
	 */
	public $fields = null;
	/**
	 * @var string
	 * @nillable
	 */
	public $where = null;
	/**
	 * @var string 
	 * @nillable
	 */
	public $limit = null;
	
	/**
	 * Converts InterAdmin_Soap_Options to array.
	 * @return array Array of $options 
	 */
	public function getArray() {
		return array(
			'fields' => jp7_explode(',', $this->fields),
			'where' => $this->where,
			'limit' => $this->limit
		);
	}
}
