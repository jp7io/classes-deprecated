<?
/**
 * JP7's PHP Functions 
 * 
 * Contains the main custom functions and classes.
 * @author JP7
 * @copyright Copyright 2002-2008 JP7 (http://jp7.com.br)
 * @category JP7
 * @package InterAdmin
 */
 
/**
 * Class which represents records on the table interadmin_{client name}.
 *
 * @package InterAdmin
 */
class InterAdmin{
	/**
	 * This record's 'id'.
	 * @var int
	 */
	public $id;
	/**
	 * This record's 'id_tipo', this values can be used to get the InterAdminTipo for a InterAdmin object.
	 * @var int
	 */
	public $id_tipo;
	/**
	 * Table prefix of this record. It is usually formed by 'interadmin_' + 'client name'.
	 * @var string
	 */
	public $db_prefix;
	/**
	 * Table suffix of this record. e.g.: the table 'interadmin_client_registrations' would have 'registrations' as $table.
	 * @var string
	 */
	public $table;
	/**
	 * Contains the InterAdminTipo, i.e. the record with an 'id_tipo' equal to this record´s 'id_tipo'.
	 * @var InterAdminTipo
	 */
	protected $_tipo;
	/**
	 * Contains the parent InterAdmin object, i.e. the record with an 'id' equal to this record's 'parent_id'.
	 * @var InterAdminTipo
	 */
	protected $_parent;
	/**
	 * Public Constructor, creates a new InterAdmin. If $options['fields'] was passed the method $this->getFieldsValues() is called.
	 * @param int $id This record's 'id'.
	 * @param array $options Array of options with the keys 'db_prefix', 'table', 'fields', and 'fields_alias'.
	 */
	public function __construct($id = 0, $options = array()) {
		$this->id = $id;
		$this->db_prefix = ($options['db_prefix']) ? $options['db_prefix'] : $GLOBALS['db_prefix'];
		$this->table = ($options['table']) ? '_' . $options['table'] : '';
		if ($options['fields']) $this->getFieldsValues($options['fields'], FALSE, $options['fields_alias']);
	}
	/**
	 * String value of this record´s 'id'.
	 * 
	 * @return Casts the string value of its $id property.
	 */
	function __toString(){
		return (string) $this->id;
	}	
	/**
	 * Gets values from this record on the database.
	 *
	 * @param array|string $fields Array (recommended) or string (an unique field) containning the names of the fields to be retrieved.
	 * @param bool $forceAsString Gets the string value for fields referencing to another InterAdmin ID (fields started by "select_").
	 * @param bool $fields_alias If <tt>TRUE</tt> the names of the fields are replaced by the Alias that were inserted on the InterAdmin.
	 * @return mixed If fields were an array an object will be returned, otherwise it will return the result as a string.
	 * @todo (Multiple languages only) When $fields_alias is <tt>TRUE</tt> and there is no id_tipo yet, the function is unable to decide which language table it should use.
	 */
	function getFieldsValues($fields, $forceAsString = FALSE, $fields_alias = FALSE) {   
		global $lang;
		if (!$this->_tipo) $this->getTipo();
		if ($this->_tipo->id_tipo /*$fields_alias*/) $campos = $this->_tipo->getCampos();
		if ($this->_tipo->id_tipo && $lang->prefix) $tipoLanguage = $this->_tipo->getFieldsValues('language');
		if ($fields == '*') {
			$fields = array();
			$invalid_fields = array('tit', 'func');
			$all_fields = array_keys($campos);
			foreach ($all_fields as $field) {
				$field_arr = explode('_', $field);
				if (!in_array($field_arr[0], $invalid_fields)) $fields[] = $field;
			}
		}
		$fieldsValues = jp7_fields_values($this->db_prefix . $this->table . (($tipoLanguage) ? $lang->prefix : ''), 'id', $this->id, $fields, TRUE);
		
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
			$alias = ($fields_alias) ? $this->_tipo->getCamposAlias($key) : $key;
			// Objeto Relacional
			if (!$forceAsString && strpos($key, 'select_') === 0) {
				if (strpos($key, 'select_multi') === 0) {
					$value_arr = explode(',', $value);
					if (!$value_arr[0]) $value_arr = array();
					foreach ($value_arr as $key2 => $value2) {
						if ($campos[$key]['xtra'] === 'S') {
							$value_arr[$key2] = new InterAdminTipo($value2);
						} else {
							$value_arr[$key2] = new InterAdmin($value2);
						}
					}
					$value = $fieldsValues->$key = $value_arr;
				} elseif ($value && is_numeric($value)) {
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
		$return_str = (array) $this->getFieldsValues($return);
		foreach ($return_str as $key=>$value) {
			if (strpos($key, 'select_') === 0 && $value) $value = $value->getStringValue();
			$return_final[] = $value;
		}
		return implode(' - ', (array) $return_final);
	}
	/**
	 * @return mixed
	 */
	function setFieldsValues($fields_values, $force_magic_quotes_gpc = FALSE){
		global $lang;
		$tipoLanguage = $this->getTipo()->getFieldsValues('language');
		if ($this->id) {
			jp7_db_insert($this->db_prefix . $this->table . (($tipoLanguage) ? $lang->prefix : ''), 'id', $this->id, $fields_values, TRUE, $force_magic_quotes_gpc);
		} else {
			$this->id = jp7_db_insert($this->db_prefix . $this->table . (($tipoLanguage) ? $lang->prefix : ''), 'id', 0, $fields_values, TRUE, $force_magic_quotes_gpc);
		}
	}
	/**
	 * @return object
	 */
	function getTipo($options = array()) {
		if (!$this->_tipo) {
			$class_name = ($options['class']) ? $options['class'] : 'InterAdminTipo';
			$this->_tipo = new $class_name($this->id_tipo, array('db_prefix' => $this->db_prefix));
		
		}
		if (!$this->id_tipo) $this->_tipo->id_tipo = $this->getFieldsValues('id_tipo');
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
	function getParent($options = array()) {
		if ($this->_parent) return $this->_parent;
		$parent_id = $this->getFieldsValues(array('parent_id'))->parent_id;
		if ($parent_id) {
			$class_name = ($options['class']) ? $options['class'] : get_class($this);
			$this->_parent = new $class_name($parent_id, array('db_prefix' => $this->db_prefix, 'table' => $this->table));
			return $this->_parent;
		}
	}
	function setParent($parent) {
		$this->_parent = $parent;
	}
	/**
	 * @param mixed $tipo
	 * @return array
	 */
	function getChildrenTipo($tipo, $options = array()) {
		$options = array_merge($options,
			array(
				'parent_id' => $this->id
			)
		);
		$childrenTipo = new InterAdminTipo($tipo, $options);
		return $childrenTipo;
	}
	/**
	 * @param mixed $tipo
	 * @return array
	 */
	function getChildren($tipo, $options = array()) {
		global $db;
		if (!$tipo) return array();
		$childrenTipo = new InterAdminTipo($tipo);
		$options['where'] .= " AND parent_id=" . $this->id;
		$children = $childrenTipo->getInterAdmins($options);
		foreach ($children as $child) {
			$child->setParent($this);
		}
		return $children;
	}
	/**
	 * @return array
	 */
	function getArquivos($options = array()) {
		global $db;
		global $lang;
		global $jp7_app;
		$arquivos = array();
	
		if ($options['fields'] == '*')  $options['fields'] = array('id_arquivo', 'id_tipo', 'id', 'parte', 'url', 'url_thumb', 'url_zoom', 'url_mac', 'nome', 'legenda', 'creditos', 'link', 'link_blank', 'mostrar', 'destaque', 'ordem', 'deleted');

		$sql = "SELECT id_arquivo" . (($options['fields']) ? ',' . implode(',', (array)$options['fields']) : '') . 
		" FROM " . $this->db_prefix .(($this->getTipo()->getFieldsValues('language')) ? $lang->prefix : '') . '_arquivos' .
		" WHERE id=" . $this->id .
		(($options['where']) ? $options['where'] : '') .
		" ORDER BY " . (($options['order']) ? $options['order'] . ',' : '') . ' ordem' .
		(($options['limit']) ? " LIMIT " . $options['limit'] : '');
		if ($jp7_app) $rs = $db->Execute($sql)or die(jp7_debug($db->ErrorMsg(), $sql));
		else $rs = interadmin_query($sql);
		while ($row = $rs->FetchNextObj()) {
			$arquivo = new InterAdminArquivo($row->id_arquivo, array('db_prefix' => $this->db_prefix));
			$arquivo->setTipo($this->getTipo());
			$arquivo->setParent($this);
			foreach((array)$options['fields'] as $field) {
				$arquivo->$field = $row->$field;
			}
			$arquivos[] = $arquivo;
		}
		$rs->Close();
		return $arquivos;
	}
	/**
	 * @return string
	 */
	function getUrl(){
		return $this->getTipo()->getUrl() . '?id=' . $this->id;
	}
}