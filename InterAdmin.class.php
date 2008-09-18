<?
/**
 * Instancia registros da tabela interadmin_cliente
 *
 */
class InterAdmin{
	public $id;
	public $id_tipo;
	/**
	 * @param int $id
	 * @param varchar $_db_prefix
	 * @return object
	 */
	function __construct($id = '', $options = NULL) {
		$this->id = $id;
		$this->db_prefix = ($options['db_prefix']) ? $options['db_prefix'] : $GLOBALS['db_prefix'];
		if ($options['fields']) $this->getFieldsValues($options['fields'], FALSE, $options['fields_alias']);
	}
	function __get($var){
		if($var == 'id'){
			return $this->$var;
		}
	}
	function __toString(){
		return (string) $this->id;
	}	
	/**
	 * Gets values from this record on the database.
	 *
	 * @param mixed $fields Array (recommended) or string (an unique field) containning the names of the fields to be retrieved.
	 * @param bool $forceAsString Gets the string value for fields referencing to another InterAdmin ID (fields started by "select_").
	 * @param bool $fields_alias If <tt>TRUE</tt> the names of the fields are replaced by the Alias that were inserted on the InterAdmin.
	 * @return mixed If fields were an array an object will be returned, otherwise it will return the result as a string.
	 * @todo (Multiple languages only) When $fields_alias is <tt>TRUE</tt> and there is no id_tipo yet, the function is unable to decide which language table it should use.
	 */
	function getFieldsValues($fields, $forceAsString = FALSE, $fields_alias = FALSE) {   
		global $lang;
		if (!$this->tipo) $this->getTipo();
		if ($this->tipo->id_tipo /*$fields_alias*/) $campos = $this->tipo->getCampos();
		if ($this->tipo->id_tipo) $tipoLanguage = $this->tipo->getFieldsValues('language');
		
		$fieldsValues = jp7_fields_values($this->db_prefix . (($tipoLanguage) ? $lang->prefix : ''), 'id', $this->id, $fields, TRUE);
		
		foreach((array)$fieldsValues as $key=>$value) {
			// Force As String
			if ($forceAsString && strpos($key, 'select_') === 0) {
				$value_arr = explode(',', $value);
				$str_arr = array();
				foreach($value_arr as $value_id) {
					$str_arr[] = jp7_fields_values($this->db_prefix . (($tipoLanguage) ? $lang->prefix : ''), 'id', $value_id, 'varchar_key');
				}
				$value = implode(', ', $str_arr);
			}
			// Fields Alias
			$alias = ($fields_alias) ? $this->tipo->getCamposAlias($key) : $key;
			// Objeto Relacional
			if (!$forceAsString && strpos($key, 'select_') === 0) {
				if (strpos($key, 'select_multi') === 0) {
					$value_arr = explode(',', $value);
					foreach ($value_arr as $key2 => $value2) {
						if ($campos[$key]['xtra'] === 'S') {
							$value_arr[$key2] = new InterAdminTipo($value2);
						} else {
							$value_arr[$key2] = new InterAdmin($value2);
						}
					}
					$value = $fieldsValues->$key = $value_arr;
				} elseif (is_numeric($value)) {
					if ($campos[$key]['xtra'] === 'S') {
						$value = $fieldsValues->$key = new InterAdminTipo($value);
					} else {
						$value = $fieldsValues->$key = new InterAdmin($value);
					}
				}
			}
			$this->$alias = $value;
		}
		if (is_array($fields)) return $fieldsValues;
		else return $fieldsValues->$fields;
	}
	/**
	 * @return string
	 */
	function getStringValue($simple = FALSE) {
		$campos = $this->getTipo()->getCampos();
		//jp7_print_r($campos);
		foreach ($campos as $key => $row) {
			if (($row['combo'] || $key == 'varchar_key' || $key == 'select_key') && $key !== 'char_key') {
				$return[] = $row['tipo'];
			}
		}
		$return_str = $this->getFieldsValues($return);
		foreach ($return_str as $key=>$value) {
			if (strpos($key, 'select_') === 0 && $value) $value = $value->getStringValue();
			$return_final[] = $value;
		}
		return implode(' - ', $return_final);
	}
	/**
	 * @return mixed
	 */
	function setFieldsValues($fields_values, $force_magic_quotes_gpc = FALSE){
		global $lang;
		$tipoLanguage = $this->getTipo()->getFieldsValues('language');
		if ($this->id) {
			jp7_db_insert($this->db_prefix . (($tipoLanguage) ? $lang->prefix : ''), 'id', $this->id, $fields_values, TRUE, $force_magic_quotes_gpc);
		} else {
			$this->id = jp7_db_insert($this->db_prefix . (($tipoLanguage) ? $lang->prefix : ''), 'id', 0, $fields_values, TRUE, $force_magic_quotes_gpc);
		}
	}
	/**
	 * @return object
	 */
	function getTipo(){
		if (!$this->tipo) $this->tipo = new InterAdminTipo($this->id_tipo, array('db_prefix' => $this->db_prefix));
		if (!$this->id_tipo) $this->tipo->id_tipo = $this->id_tipo = $this->getFieldsValues('id_tipo');
		return $this->tipo;
	}
	/**
	 * @param mixed $tipo
	 * @return array
	 */
	function getChildren($tipo, $options = NULL) {
		global $db;
		if (!$tipo) return;
		$children_tipo = new InterAdminTipo($tipo);
		$options['where'] = " AND parent_id=" . $this->id;
		return $children_tipo->getInterAdmins($options);
	}
	/**
	 * @return string
	 */
	function getURL(){
		return $this->getTipo()->getURL() . '?id=' . $this->id;
	}
}
?>