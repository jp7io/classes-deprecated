<?php
/**
 * JP7's PHP Functions 
 * 
 * Contains the main custom functions and classes.
 * @author JP7
 * @copyright Copyright 2002-2008 JP7 (http://jp7.com.br)
 * @category JP7
 * @package InterAdminArquivo
 */
 
/**
 * Class which represents records on the table interadmin_{client name}_arquivos.
 *
 * @package InterAdminArquivo
 */
class InterAdminArquivo{
	public $id_arquivo;
	public $id;
	public $id_tipo;
	public $db_prefix;
	protected $_tipo;
	protected $_parent;
	/**
	 * @param int $id
	 * @param array $options
	 */
	function __construct($id_arquivo = 0, $options = array()) {
		$this->id_arquivo = $id_arquivo;
		$this->db_prefix = ($options['db_prefix']) ? $options['db_prefix'] : $GLOBALS['db_prefix'];
		if ($options['fields']) $this->getFieldsValues($options['fields']);
	}
	function __toString(){
		return (string) $this->id_arquivo;
	}	
	/**
	 * Gets values from this record on the database.
	 *
	 * @param mixed $fields Array (recommended) or string (an unique field) containning the names of the fields to be retrieved.
	 * @return mixed If fields were an array an object will be returned, otherwise it will return the result as a string.
	 */
	function getFieldsValues($fields) {   
		global $lang;
		if (!$this->_tipo) $this->getTipo();
		if ($this->_tipo->id_tipo) $campos = $this->_tipo->getCampos();
		if ($this->_tipo->id_tipo && $lang->prefix) $tipoLanguage = $this->_tipo->getFieldsValues('language');
		
		$fieldsValues = jp7_fields_values($this->db_prefix . (($tipoLanguage) ? $lang->prefix : '') . '_arquivos', 'id_arquivo', $this->id_arquivo, $fields, TRUE);
		foreach ((array) $fieldsValues as $field=>$value) {
			$this->$field = $value;
		}
		if (is_array($fields)) return $fieldsValues;
		else return $fieldsValues->$fields;
	}
	/**
	 * @return mixed
	 */
	function setFieldsValues($fields_values, $force_magic_quotes_gpc = FALSE) {
		global $lang;
		$tipoLanguage = $this->getTipo()->getFieldsValues('language');
		if ($this->id_arquivo) {
			jp7_db_insert($this->db_prefix . (($tipoLanguage) ? $lang->prefix : '')  . '_arquivos', 'id_arquivo', $this->id_arquivo, $fields_values, TRUE, $force_magic_quotes_gpc);
		} else {
			$this->id_arquivo = jp7_db_insert($this->db_prefix . (($tipoLanguage) ? $lang->prefix : '')  . '_arquivos', 'id_arquivo', 0, $fields_values, TRUE, $force_magic_quotes_gpc);
		}
	}
	/**
	 * @return object
	 */
	function getTipo() {
		if (!$this->_tipo) $this->_tipo = new InterAdminTipo($this->id_tipo, array('db_prefix' => $this->db_prefix));
		if (!$this->id_tipo) $this->_tipo->id_tipo = $this->id_tipo = $this->getFieldsValues('id_tipo');
		return $this->_tipo;
	}
	/**
	 *
	 */
	function setTipo($tipo) {
		$this->id_tipo = $tipo->id_tipo;
		$this->_tipo = $tipo;
	}
	/**
	 * @return InterAdmin
	 */
	function getParent() {
		if ($this->_parent) return $this->_parent;
		$id = $this->getFieldsValues(array('id'))->id;
		if ($id) {
			$this->_parent = new InterAdmin($id, array('db_prefix' => $this->db_prefix));
			return $this->_parent;
		}
	}
	function setParent($parent) {
		$this->_parent = $parent;
	}
}
?>