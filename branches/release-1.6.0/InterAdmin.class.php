<?php
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
class InterAdmin extends InterAdminAbstract {
	/**
	 * Table prefix of this record. It is usually formed by 'interadmin_' + 'client name'.
	 * @var string
	 * @deprecated It will only use this property if there is no id_tipo yet
	 */
	public $db_prefix;
	/**
	 * Table suffix of this record. e.g.: the table 'interadmin_client_registrations' would have 'registrations' as $table.
	 * @var string
	 * @deprecated It will only use this property if there is no id_tipo yet
	 */
	public $table;
	/**
	 * Contains the InterAdminTipo, i.e. the record with an 'id_tipo' equal to this record´s 'id_tipo'.
	 * @var InterAdminTipo
	 */
	protected $_tipo;
	/**
	 * Contains the parent InterAdmin object, i.e. the record with an 'id' equal to this record's 'parent_id'.
	 * @var InterAdmin
	 */
	protected $_parent;
	/**
	 * Contains an array of objects (InterAdmin and InterAdminTipo).
	 * @var array
	 */
	protected $_tags;
	/**
	 * Public Constructor. If $options['fields'] was passed the method $this->getFieldsValues() is called.
	 * @param int $id This record's 'id'.
	 * @param array $options Default array of options. Available keys: db_prefix, table, fields, fields_alias.
	 */
	public function __construct($id = 0, $options = array()) {
		if ($id) {
			$this->id = $id;
		}
		$this->db_prefix = ($options['db_prefix']) ? $options['db_prefix'] : $GLOBALS['db_prefix'];
		$this->table = ($options['table']) ? '_' . $options['table'] : '';
		if ($options['fields']) {
			$this->getFieldsValues($options['fields'], false, $options['fields_alias']);
		}
	}
	/**
	 * Returns an InterAdmin instance. If $options['class'] is passed, 
	 * it will be returned an object of the given class, otherwise it will search 
	 * on the database which class to instantiate.
	 *
	 * @param int $id This record's 'id'.
	 * @param array $options Default array of options. Available keys: db_prefix, table, fields, fields_alias, class, default_class.
	 * @param InterAdminTipo Set the record´s Tipo.
	 * @return InterAdmin Returns an InterAdmin or a child class in case it's defined on the 'class' property of its InterAdminTipo.
	 */
	public static function getInstance($id, $options = array(), InterAdminTipo $tipo = null) {
		if (!$options['default_class']) {
			$options['default_class'] = 'InterAdmin';
		}
		if ($tipo && !$options['class'] && $tipo->class) {
			$options['class'] = $tipo->class;
		}
		if ($options['class']) {
			$class_name = (class_exists($options['class'])) ? $options['class'] : $options['default_class'];
			$finalInstance = new $class_name($id, $options);
			if ($tipo) {
				$finalInstance->setTipo($tipo);
			}
		} else {
			$instance = new $options['default_class']($id, array_merge($options, array('fields' => array())));
			if ($tipo) {
				$instance->setTipo($tipo);
			}
			$class_name = $instance->getTipo()->class;
			if (class_exists($class_name)) {
				$finalInstance = new $class_name($id, $options);
				$finalInstance->setTipo($instance->getTipo()); // Performance
			} else {
				if ($options['fields']) {
					$instance->getFieldsValues($options['fields'], false, $options['fields_alias']);
				}
				$finalInstance = $instance;
			}
		}
		return $finalInstance;
	}
	/**
	 * String value of this record´s $id.
	 * 
	 * @return string String value of the $id property.
	 */
	public function __toString(){
		return (string) $this->id;
	}
	/**
	 * Gets fields values by their alias.
	 *  
	 * @param array|string $fields
	 * @see InterAdmin::getFieldsValues()
	 * @return 
	 */
	public function getByAlias($fields) {
		if (func_num_args() > 1) {
			throw new Exception('Only 1 argument is expected and it should be an array.');
		}
		return $this->getFieldsValues($fields, false, true);
	}
	/**
	 * Gets the InterAdminTipo object for this record, which is then cached on the $_tipo property.
	 * 
	 * @param array $options Default array of options. Available keys: class.
	 * @return InterAdminTipo
	 */
	public function getTipo($options = array()) {
		if (!$this->_tipo) {
			if (!$id_tipo = $this->id_tipo) {
				$id_tipo = jp7_fields_values($this->getTableName(), 'id', $this->id, 'id_tipo');
			}
			$this->setTipo(InterAdminTipo::getInstance($id_tipo, array(
				'db_prefix' => $this->db_prefix,
				'class' => $options['class']
			)));
		}
		return $this->_tipo;
	}
	/**
	 * Sets the InterAdminTipo object for this record, changing the $_tipo property.
	 *
	 * @param InterAdminTipo $tipo
	 * @return void
	 */
	public function setTipo($tipo) {
		unset($this->db_prefix);
		unset($this->table);
		$this->id_tipo = $tipo->id_tipo;
		$this->_tipo = $tipo;
	}
	/**
	 * Gets the parent InterAdmin object for this record, which is then cached on the $_parent property.
	 * 
	 * @param array $options Default array of options. Available keys: db_prefix, table, fields, fields_alias, class.
	 * @return InterAdmin
	 */
	public function getParent($options = array()) {
		if (!$this->parent_id) {
			$this->getFieldsValues('parent_id');
		}
		if (!$this->_parent) {
			$options['default_class'] = $this->_getDefaultClass();
			$this->_parent = InterAdmin::getInstance($this->parent_id, $options);
			if ($this->_parent->id) {
				$this->getTipo()->setParent($this->_parent);
			}
		}
		return $this->_parent;
	}
	/**
	 * Sets the parent InterAdmin object for this record, changing the $_parent property.
	 *
	 * @param InterAdmin $parent
	 * @return void
	 */
	public function setParent($parent) {
		$this->_parent = $parent;
	}
	/**
	 * Instantiates an InterAdminTipo object and sets this record as its parent.
	 * 
	 * @param int $id_tipo
	 * @param array $options Default array of options. Available keys: db_prefix, fields, class.
	 * @return InterAdminTipo
	 */
	public function getChildrenTipo($id_tipo, $options = array()) {
		if (!$options['db_prefix']) {
			$options['db_prefix'] = $this->getTipo()->db_prefix;
		}
		$options['default_class'] = $this->_getDefaultClass() . 'Tipo';
		$childrenTipo = InterAdminTipo::getInstance($id_tipo, $options);
		$childrenTipo->setParent($this);
		return $childrenTipo;
	}
	/**
	 * Retrieves this record´s children for the given $id_tipo.
	 * 
	 * @param int $id_tipo
	 * @param array $options Default array of options. Available keys: fields, where, order, group, limit, class.
	 * @return array Array of InterAdmin objects.
	 */
	public function getChildren($id_tipo, $options = array()) {
		global $db;
		$children = array();
		if ($id_tipo) {
			$children = $this->getChildrenTipo($id_tipo)->getInterAdmins($options);
		}
		return $children;
	}
	/**
	 * Retrieves the uploaded files of this record.
	 * 
	 * @param array $options Default array of options. Available keys: fields, where, order, limit.
	 * @return array Array of InterAdminArquivo objects.
	 */
	public function getArquivos($options = array()) {
		global $db;
		global $lang;
		global $jp7_app;
		$arquivos = array();
		
		$className = (class_exists($options['class'])) ? $options['class'] : 'InterAdminArquivo';
		
		if ($options['fields'] == '*') {
			$options['fields'] = call_user_method('getAllFieldsNames', $className);
		}
		
		if (is_array($options['where'])) {
			$options['where'] = ' AND ' . implode(' AND ', $options['where']);
		}
		
		$sql = "SELECT id_arquivo" . (($options['fields']) ? ',' . implode(',', (array)$options['fields']) : '') . 
			" FROM " . $this->db_prefix .(($this->getTipo()->getFieldsValues('language')) ? $lang->prefix : '') . '_arquivos' .
			" WHERE id_tipo = " . intval($this->id_tipo) . " AND id=" . $this->id .
			(($options['where']) ? $options['where'] : '') .
			" ORDER BY " . (($options['order']) ? $options['order'] . ',' : '') . ' ordem' .
			(($options['limit']) ? " LIMIT " . $options['limit'] : '');
		if ($jp7_app) $rs = $db->Execute($sql)or die(jp7_debug($db->ErrorMsg(), $sql));
		else $rs = interadmin_query($sql);
		while ($row = $rs->FetchNextObj()) {
			$arquivo = new $className($row->id_arquivo, array('db_prefix' => $this->db_prefix));
			$arquivo->setTipo($this->getTipo());
			$arquivo->setParent($this);
			foreach((array) $options['fields'] as $field) {
				$arquivo->$field = $row->$field;
			}
			$arquivos[] = $arquivo;
		}
		$rs->Close();
		return $arquivos;
	}
	/**
	 * Returns the full url for this record.
	 * 
	 * @return string
	 */
	public function getUrl(){
		global $seo;
		if ($this->getParent()->id) {
			$link = $this->_parent->getUrl() . '/' . toSeo($this->getTipo()->getFieldsValues('nome'));
		} else {
			$link = $this->getTipo()->getUrl();
		}
		if ($seo) {
			$alias = $this->getTipo()->getCamposAlias('varchar_key');
			if (isset($this->$alias)) {
				$nome = $this->$alias;
			} else {
				$nome = $this->getFieldsValues('varchar_key');
			}
			$link .= '.' . toSeo($nome);
		} else {
			$link .= '?id=' . $this->id;
		}
		return $link;
	}
	/**
	 * Returns the tags.
	 * 
	 * @return string
	 */
	public function getTags($class_tipo = 'InterAdminTipo', $class = 'InterAdmin'){
		if (!$this->_tags) {
			global $db;
			$sql = "SELECT * FROM " . $this->db_prefix . "_tags WHERE parent_id = " . $this->id;
			$rs = $db->Execute($sql);
			$this->tags = array();
			while ($row = $rs->FetchNextObj()) {
				$tag_tipo = new InterAdminTipo($row->id_tipo);
				$tag_text = $tag_tipo->getFieldsValues('nome');
				if ($row->id) {
					$options = array(
						'fields' => array('varchar_key'),
						'where' => array('id=' . $row->id)
					);
					$tag_registro = $tag_tipo->getFirstInterAdmin($options);
					$tag_text = $tag_registro->varchar_key . ' (' . $tag_tipo->nome . ')';
					$tag_registro->interadmin = $this;
					$this->_tags[] = $tag_registro;
				} else {
					$tag_tipo->interadmin = $this;
					$this->_tags[] = $tag_tipo;
				}
			}
			$rs->Close();
		}
		return (array) $this->_tags;
	}
	/**
	 * Checks if this object is published using the same rules used on interadmin_query().
	 * 
	 * @return bool
	 */
	public function isPublished() {
		global $c_publish, $s_session;
		$this->getFieldsValues(array('date_publish', 'date_expire', 'char_key', 'publish', 'deleted'));
		return (
			strtotime($this->date_publish) <= time() &&
			(strtotime($this->date_expire) >= time() || $this->date_expire == '0000-00-00 00:00:00') &&
			$this->char_key &&
			($this->publish || $s_session['preview'] || !$c_publish) &&
			!$this->deleted
		);
	}
	/**
	 * Gets the string value for fields referencing to another InterAdmin ID (fields started by "select_").
	 * 
	 * @param array $sqlRow
	 * @param string $tipoLanguage
	 * @deprecated Kept for backwards compatibility
	 * @return mixed
	 */
	protected function _getFieldsValuesAsString($sqlRow, $tipoLanguage) {
		global $lang;
		foreach((array) $sqlRow as $key => $value) {
			if (strpos($key, 'select_') === 0) {
				$value_arr = explode(',', $value);
				$str_arr = array();
				foreach($value_arr as $value_id) {
					$str_arr[] = jp7_fields_values($this->db_prefix . (($tipoLanguage) ? $lang->prefix : ''), 'id', $value_id, 'varchar_key');
				}
				$value = implode(', ', $str_arr);
			}
			if ($fields_alias) {
				$alias = $this->_tipo->getCamposAlias($key);
				unset($sqlRow->$key);
			} else {
				$alias = $key;
			}
			$this->$alias = $sqlRow->$alias = $value;
		}
		
		if (is_array($fields)) {
			return $sqlRow;
		} else {
			return $sqlRow->$fields;
		}
	}
	/**
	 * Returns this object´s varchar_key and all the fields marked as 'combo', if the field 
	 * is an InterAdmin such as a select_key, its getStringValue() method is used.
	 *
	 * @return string For the city 'Curitiba' with the field 'state' marked as 'combo' it would return: 'Curitiba - Paraná'.
	 */
	public function getStringValue() {
		$campos = $this->getTipo()->getCampos();
		$camposCombo = array();
		foreach ($campos as $key => $campo) {
			if (($campo['combo'] || $key == 'varchar_key' || $key == 'select_key') && $key !== 'char_key') {
				$camposCombo[] = $campo['tipo'];
			}
		}
		$valoresCombo = $this->getFieldsValues($camposCombo);
		$stringValue = array();
		foreach ($valoresCombo as $key => $value) {
			if (is_object($value)) {
				 $value = $value->getStringValue();
			}
			$stringValue[] = $value;
		}
		return implode(' - ', $stringValue);
	}
	/**
	 * Saves this record and updates date_modify.
	 * 
	 * @return void
	 */
	public function save() {
		$this->date_modify = date('c');
		return parent::save();
	}
	public function getAttributesNames() {
		return $this->getTipo()->getCamposNames();
	}
	public function getAttributesCampos() {
		return $this->getTipo()->getCampos();
	}
	public function getAttributesAliases() {
		return $this->getTipo()->getCamposAlias();
	}
	public function getTableName() {
		if ($this->id_tipo) {
			return $this->getTipo()->getInterAdminsTableName();
		} else {
			// Compatibilidade, tenta encontrar na tabela global
			return $this->db_prefix . $this->table;
		}
	}
}
