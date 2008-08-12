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
		return $this->id;
	}	
	/**
	 * @param mixed $fields
	 * @param bool $forceAsString Gets the string value for fields referencing to another InterAdmin ID
	 * @return mixed
	 */
	function getFieldsValues($fields, $forceAsString = FALSE, $fields_alias = FALSE, $fromtipo = FALSE) {   
		global $lang;
		// Prevents function from entering in an infinite looping
		if (!$this->tipo && !$fromtipo) $this->getTipo();
		if ($fields_alias) $campos = $this->tipo->getCampos();
		if ($this->tipo) $tipo_language = $this->tipo->getFieldsValues('language');
		
		$fieldsValues = jp7_fields_values($this->db_prefix . (($tipo_language) ? $lang->prefix : ''), 'id', $this->id, $fields, TRUE);
		
		foreach((array)$fieldsValues as $key=>$value) {
			if ($forceAsString && strpos($key, 'select_') === 0) $value = jp7_fields_values($this->db_prefix . (($tipo_language) ? $lang->prefix : ''), 'id', $value, 'varchar_key');
			if ($fields_alias) {
				if ($campos[$key]['nome_id']) {
					$alias = $campos[$key]['nome_id'];
				} else {
					$alias = $campos[$key]['nome'];
					if (is_numeric($alias)) {
						$alias = jp7_fields_values($this->db_prefix . '_tipos', 'id_tipo', $alias, 'nome');
					}
					$alias = toId($alias);
				}
			} else {
				$alias = $key;
			}
			$this->$alias = $value;
		}
		if (is_array($fields)) return $fieldsValues;
		else return $fieldsValues->$fields;
	}
	/**
	 * @return mixed
	 */
	function setFieldsValues($fields_values, $force_magic_quotes_gpc=FALSE){
		global $lang;
		$tipo_language = $this->getTipo()->getFieldsValues('language');
		if ($this->id) {
			jp7_db_insert($this->db_prefix . (($tipo_language) ? $lang->prefix : ''), 'id', $this->id, $fields_values, TRUE, $force_magic_quotes_gpc);
		} else {
			$this->id = jp7_db_insert($this->db_prefix . (($tipo_language) ? $lang->prefix : ''), 'id', 0, $fields_values, TRUE, $force_magic_quotes_gpc);
		}
	}
	/**
	 * @return object
	 */
	function getTipo(){
		if (!$this->id_tipo) $this->id_tipo = $this->getFieldsValues('id_tipo', FALSE, FALSE, TRUE);
		if (!$this->tipo) $this->tipo = new InterAdminTipo($this->id_tipo, array('db_prefix' => $this->db_prefix));
		return $this->tipo;
	}
	/**
	 * @param mixed $tipo
	 * @return array
	 */
	function getChildren($tipo, $options = NULL) {
		global $db;
		$children_tipo = new InterAdminTipo($tipo);
		$options['where'] = " AND parent_id=".$this->id;
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