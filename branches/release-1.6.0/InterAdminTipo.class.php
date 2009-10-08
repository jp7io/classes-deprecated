<?php
/**
 * JP7's PHP Functions 
 * 
 * Contains the main custom functions and classes.
 * @author JP7
 * @copyright Copyright 2002-2008 JP7 (http://jp7.com.br)
 * @category JP7
 * @package InterAdminTipo
 */
 
/**
 * Class which represents records on the table interadmin_{client name}_tipos.
 * 
 * @property string $interadminsOrderby SQL Order By for the records of this InterAdminTipo.
 * @property string $class Class to be instantiated for the records of this InterAdminTipo.
 * @property string $tabela Table of this Tipo, or of its Model, if it has no table.
 * @category Jp7
 * @package InterAdminTipo
 */
class InterAdminTipo extends InterAdminAbstract {
	const DEFAULT_CLASS = 'InterAdmin';	
	
 	/**
	 * Stores metadata to be shared by instances with the same $id_tipo.
	 * @var array 
	 */
	protected static $_metadata;
	protected $_primary_key = 'id_tipo';
	/**
	 * Table prefix of this record. It is usually formed by 'interadmin_' + 'client name'.
	 * @var string
	 */
	public $db_prefix;
	/**
	 * Caches the url retrieved by getUrl().
	 * @var string
	 */
	protected $_url;
	/**
	 * Contains the parent InterAdminTipo object, i.e. the record with an 'id_tipo' equal to this record's 'parent_id_tipo'.
	 * @var InterAdminTipo
	 */
	protected $_parent;
				
	/**
	 * Public Constructor. If $options['fields'] was passed the method $this->getFieldsValues() is called.
	 * 
	 * @param int $id_tipo This record's 'id_tipo'.
	 * @param array $options Default array of options. Available keys: db_prefix, fields.
	 */
	public function __construct($id_tipo = 0, $options = array()) {
		$this->id_tipo = $id_tipo;
		$this->db_prefix = ($options['db_prefix']) ? $options['db_prefix'] : $GLOBALS['db_prefix'];
		if ($options['fields']) {
			$this->getFieldsValues($options['fields']);
		}
	}
	/**
	 * Returns an InterAdminTipo instance. If $options['class'] is passed, 
	 * it will be returned an object of the given class, otherwise it will search 
	 * on the database which class to instantiate.
	 *
	 * @param int $id_tipo This record's 'id_tipo'.
	 * @param array $options Default array of options. Available keys: db_prefix, fields, class, default_class.
	 * @return InterAdminTipo Returns an InterAdminTipo or a child class in case it's defined on its 'class_tipo' property.
	 */
	public static function getInstance($id_tipo, $options = array()){
		if (!$options['default_class']) {
			$options['default_class'] = 'InterAdminTipo';
		}
		if ($options['class']) {
			$class_name = (class_exists($options['class'])) ? $options['class'] : $options['default_class'];
		} else {
			$instance = new $options['default_class']($id_tipo, array_merge($options, array('fields' => array('model_id_tipo', 'class_tipo'))));
			if ($instance->class_tipo) {
				 $class_name = $instance->class_tipo;
			} else {
				// @todo Store class_tipo on metadatas do save queries
				$class_name = jp7_fields_values($instance->db_prefix . '_tipos', 'id_tipo', $instance->model_id_tipo, 'class_tipo');
			}
			if (!class_exists($class_name)) {
				if ($options['fields']) $instance->getFieldsValues($options['fields']);
				return $instance;
			}
		}
		return new $class_name($id_tipo, $options);
	}
	
	
	public function getFieldsValues($fields, $forceAsString = false, $fieldsAlias = false, $reloadValues = false) {
		if (!isset($this->attributes['model_id_tipo'])) {
			$eagerload = array('nome', 'campos', 'model_id_tipo', 'tabela', 'class', 'class_tipo', 'template');
			$neededFields = array_unique(array_merge((array) $fields, $eagerload));
			$values = parent::getFieldsValues($neededFields, $forceAsString, $fieldsAlias, $reloadValues);
			if (is_array($fields)) {
				return $values;
			} else {
				return $values->$fields;
			}
		}
		return parent::getFieldsValues($fields, $forceAsString, $fieldsAlias, $reloadValues);
	}
	
	/**
	 * String value of this record´s $id_tipo.
	 *
	 * @return string String value of the $id_tipo property.
	 */
	public function __toString() {
		return (string) $this->id_tipo;
	}
	/**
	 * Retrieves magic properties.
	 *
	 * @param string $var Magic property 'interadminsOrderby' or 'class'.
	 * @return mixed
	 */
	public function __get($var) {
		if (!isset($this->attributes[$var])) {
			if ($var == 'class' || $var == 'tabela') {
				if (!$this->$var && !$this->getFieldsValues($var)) {
					$this->$var = $this->getModel()->getFieldsValues($var);
				}
			}
		}
		return parent::__get($var);
	}
	/**
	 * Gets the parent InterAdminTipo object for this record, which is then cached on the $_parent property.
	 * 
	 * @param array $options Default array of options. Available keys: db_prefix, fields, class.
	 * @return InterAdminTipo
	 */
	public function getParent($options = array()) {
		if ($this->_parent) return $this->_parent;
		if ($this->parent_id_tipo || $this->getFieldsValues('parent_id_tipo')) {
			$options['default_class'] = $this->_getDefaultClass() . 'Tipo';
			return $this->_parent = InterAdminTipo::getInstance($this->parent_id_tipo, $options);
		}
	}
	/**
	 * Sets the parent InterAdminTipo or InterAdmin object for this record, changing the $_parent property.
	 *
	 * @param InterAdminTipo|InterAdmin $parent
	 * @return void
	 */
	public function setParent($parent) {
		$this->_parent = $parent;
	}
	/**
	 * Retrieves the children of this InterAdminTipo.
	 * 
	 * @param array $options Default array of options. Available keys: fields, where, order, class.
	 * @return array Array of InterAdminTipo objects.
	 */
	public function getChildren($options = array()) {
		// FIXME temporário para wheres que eram com string
		if (!is_array($options['where'])) {
			if ($options['where']) {
				$options['where'] = jp7_explode(' AND ', $options['where']);
			} else {
				$options['where'] = array();
			}
		}
		
		$options['fields'] = array_merge(array('id_tipo'), (array) $options['fields']);
		$options['from'] = $this->getTableName() . " AS main";
		$options['where'][] = "parent_id_tipo = " . $this->id_tipo;
	 	if (!$options['order']) {
	 		$options['order'] = 'ordem, nome';
		}
		
		$attributesArray = $this->_executeQuery($options, $this->getAttributesCampos(), $this->getAttributesAliases());
		
		$tipos = array();
		foreach ($attributesArray as $attributes) {
			$tipo = InterAdminTipo::getInstance($attributes['id_tipo'], array(
				'db_prefix' => $this->db_prefix,
				'class' => $options['class'],
				'default_class' => $this->_getDefaultClass() . 'Tipo'
			));
			$tipo->setParent($this);
			$tipo->attributes = array_merge($tipo->attributes, $attributes);
			$tipos[] = $tipo;
		}
		return $tipos;
	}
	/**
	 * Retrieves the children of this InterAdminTipo which have the given model_id_tipo.
	 * 
	 * @param array $options Default array of options. Available keys: fields, where, order, class.
	 * @return Array of InterAdminTipo objects.
	 */
	public function getChildrenByModel($model_id_tipo, $options = array()) {
		$options['where'][] = "model_id_tipo = " . $model_id_tipo;
		return $this->getChildren($options);
	}
	/**
	 * Retrieves the records which have this InterAdminTipo's id_tipo.
	 * 
	 * @param array $options Default array of options. Available keys: fields, where, order, group, limit, class.
	 * @return array Array of InterAdmin objects.
	 */
	public function getInterAdmins($options = array()) {
		if ($options['fields'] == '*') {
			$options['fields'] = $this->getCamposNames();
		}
		// FIXME temporário para wheres que eram com string
		if (!is_array($options['where'])) {
			if ($options['where']) {
				$options['where'] = explode(' AND ', $options['where']);
				$options['where'] = array_filter($options['where'], 'array_trim'); // Para remover itens vazios
			} else {
				$options['where'] = array();
			}
		}
		
		$options['fields'] = array_merge(array('id'), (array) $options['fields']);
		$options['from'] = $this->getInterAdminsTableName() . " AS main";
		$options['where'][] = "id_tipo = " . $this->id_tipo;
		if ($this->_parent && $this->_parent instanceof InterAdmin) {
			$options['where'][] =  "parent_id = " . $this->_parent->id;
		}
		$options['order'] = (($options['order']) ? $options['order'] . ',' : '') . $this->getInterAdminsOrder();
		
		$attributesArray = $this->_executeQuery($options, $this->getCampos(), $this->getCamposAlias());
		
		$records = array();
		foreach ($attributesArray as $attributes) {
			$record = InterAdmin::getInstance($attributes['id'], array(
				'class' => $options['class'],
				'default_class' => $this->_getDefaultClass()
			), $this);
			$record->setTipo($this);
			if ($this->_parent instanceof InterAdmin) {
				$record->setParent($this->_parent);
			}
			$record->attributes = array_merge($record->attributes, $attributes);
			$records[] = $record;
		}
		return $records;
	}
	/**
	 * Returns the number of InterAdmins using COUNT(id).
	 *
	 * @param array $options Default array of options. Available keys: where.
	 * @return int Count of InterAdmins found.
	 */
	public function getInterAdminsCount($options = array()) {
		$options['fields'] = array('COUNT(id)');
		$retorno = $this->getFirstInterAdmin($options);
		return intval($retorno->count_id);
	}
	/**
	 * Retrieves the first records which have this InterAdminTipo's id_tipo.
	 * 
	 * @param array $options Default array of options. Available keys: fields, where, order, group, class.
	 * @return InterAdmin First InterAdmin object found.
	 */
	public function getFirstInterAdmin($options = array()) {
		$options['limit'] = 1;
		$interAdmin = $this->getInterAdmins($options);
		return $interAdmin[0];
	}
	/**
	 * Retrieves the unique record which have this id
	 * 
	 * @param int $id Search value.
	 * @return InterAdmin First InterAdmin object found.
	 */
	public function getInterAdminById($id, $options = array()) {
		$options['where'] = array("id = " . intval($id));
		return $this->getFirstInterAdmin($options);
	}
	/**
	 * Retrieves the first record which have this id_string
	 * 
	 * @param string $id_string Search value.
	 * @return InterAdmin First InterAdmin object found.
	 */
	public function getInterAdminByIdString($id_string, $options = array()) {
		$options['where'] = array("id_string = '" . $id_string . "'");
		return $this->getFirstInterAdmin($options);
	}
	/**
	 * Returns the model identified by model_id_tipo, or the object itself if it has no model.
	 *
	 * @param array $options Default array of options. Available keys: db_prefix, fields.
	 * @return InterAdminTipo Model used by this InterAdminTipo.
	 */
	public function getModel($options = array()) {
		if ($this->model_id_tipo || $this->getFieldsValues('model_id_tipo')) {
			$model = new InterAdminTipo($this->model_id_tipo, $options);
			return $model->getModel($options);
		} else {
			return $this;
		}
	}
	/**
	 * Returns an array with data about the fields on this type, which is then cached on the $_campos property.
	 * 
	 * @return array
	 */
	public function getCampos() {
		if (!$A = $this->_getMetadata('campos')) {
			$model = $this->getModel();
			$campos = $model->getFieldsValues('campos');
			unset($model->campos);
			$campos_parameters = array(
				'tipo', 'nome', 'ajuda', 'tamanho', 'obrigatorio', 'separador', 'xtra',
				'lista', 'orderby', 'combo', 'readonly', 'form', 'label', 'permissoes',
				'default', 'nome_id'
			);
			$campos	= split('{;}', $campos);
			$A = array();
			for ($i = 0; $i < count($campos); $i++) {
				$parameters = split("{,}", $campos[$i]);
				if ($parameters[0]) {
					$A[$parameters[0]]['ordem'] = ($i+1);
					$isSelect = (strpos($parameters[0], 'select_') !== false);
					for ($j = 0 ; $j < count($parameters); $j++) {
						$A[$parameters[0]][$campos_parameters[$j]] = $parameters[$j];
					}
					if ($isSelect && $A[$parameters[0]]['nome'] != 'all') {
						$id_tipo = $A[$parameters[0]]['nome'];
						$A[$parameters[0]]['nome'] = new InterAdminTipo($id_tipo);
					}
				}
			}
			$this->_setMetadata('campos', $A);
		}
		return $A;
	}
	/**
	 * Returns an array with the names of all the fields available.
	 * 
	 * @return array
	 */
	public function getCamposNames(){
		$invalid_fields = array('tit', 'func');
		$fields = array_keys($this->getCampos());
		foreach ($fields as $key => $field) {
			$field_arr = explode('_', $field);
			if (in_array($field_arr[0], $invalid_fields)) {
				unset($fields[$key]);
			}
		}
		return $fields;
	}
	/**
	 * Gets the alias for a given field name.
	 * 
	 * @param array|string $fields Fields names, defaults to all fields.
	 * @return array|string Resulting alias(es).
	 */
	public function getCamposAlias($fields = null) {
		$campos = $this->getCampos();
		if (is_null($fields)) {
			$fields = array_keys($campos);
		}
		$aliases = array();
		foreach ((array) $fields as $field) {
			if ($campos[$field]['nome_id']) {
				$aliases[$field] = $campos[$field]['nome_id'];
			} else {
				$alias = $campos[$field]['nome'];
				if (!$alias) {
					// Alias magico para imagem_1 trazer file_1 e file_1_text.
					$imagemField = preg_replace('/_text$/', '', $field);
					if ($campos[$imagemField]) {
						$alias = $this->getCamposAlias($imagemField) . '_text';
					}
				}
				if (is_object($alias)) {
					$alias = ($alias->nome) ? $alias->nome : $alias->getFieldsValues('nome');
				}
				$alias = ($alias) ? toId($alias, false, '_') : $field;
				$aliases[$field] = $alias;
				// Cache
				$campos[$field]['nome_id'] = $alias; 
				$this->_setMetadata('campos', $campos);
			}
		}
		if (is_array($fields)) {
			return $aliases;
		} else {
			return reset($aliases);
		}
	}
	/**
	 * Returns this object´s nome and all the fields marked as 'combo', if the field 
	 * is an InterAdminTipo such as a select_key, its getStringValue() method is used.
	 *
	 * @return string For the tipo 'City' with the field 'state' marked as 'combo' it would return: 'City - State'.
	 */
	public function getStringValue(/*$simple = FALSE*/) {
		$campos = $this->getCampos();
		$return[] = $this->getFieldsValues('nome');
		//if (!$simple) {
			foreach ($campos as $key => $row) {
				if (($row['combo'] || $key == 'varchar_key' || $key == 'select_key') && $key !== 'char_key') {
					if (is_object($row['nome'])) $return[] = $row['nome']->getStringValue();
					else $return[] = $row['nome'];
				}
			}
		//}
		return implode(' - ', $return);
	}
	/**
	 * Returns the full url for this InterAdminTipo.
	 * 
	 * @return string
	 */
	public function getUrl() {
		if ($this->_url) {
			return $this->_url;
		}
		global $c_url, $c_cliente_url, $c_cliente_url_path, $implicit_parents_names, $jp7_app, $seo, $lang;
		$url = '';
		$url_arr = '';
		$parent = $this;
		while ($parent) {
			if (!isset($parent->nome)) {
				$parent->getFieldsValues('nome');
			}
			if ($seo) {
				if (!in_array($parent->nome, (array) $implicit_parents_names)) $url_arr[] = toSeo($parent->nome);
			} else {
				if (toId($parent->nome)) {
					$url_arr[] = toId($parent->nome);
				}
			}
			$parent = $parent->getParent();
			if ($parent instanceof InterAdmin) $parent = $parent->getTipo();
		}
		$url_arr = array_reverse((array) $url_arr);

		if ($seo) {
			$url = $c_url . join("/", $url_arr);
		} else {
			$url = (($jp7_app) ? $c_cliente_url . $c_cliente_url_path : $c_url) . $lang->path_url . join("_", $url_arr);
			$pos = strpos($url, '_');
			if ($pos) $url = substr_replace($url, '/', $pos, 1);
			$url .= (count($url_arr) > 1) ? '.php' : '/';
		}
		return $this->_url = $url;
	}
	/**
	 * Returns the names of the parents separated by '/', e.g. 'countries/south-america/brazil'.
	 * 
	 * @return string
	 */
	public function getTreePath() {
		global $c_url, $implicit_parents_names, $seo, $lang;
		$url = '';
		$url_arr = '';
		$parent = $this;
		while ($parent) {
			if ($seo) {
				if (!in_array($parent->getFieldsValues('nome'), (array)$implicit_parents_names)) $url_arr[] = toSeo($parent->getFieldsValues('nome'));
			} else {
				$url_arr[] = $parent->getFieldsValues('nome');
			}
			$parent = $parent->getParent();
		}
		$url_arr = array_reverse((array)$url_arr);

			$url = $c_url . join("/", $url_arr);
		return $url;
	}
	public function getAttributesNames() {
		return array('id_tipo', 'model_id_tipo', 'parent_id_tipo', 'redirect_id_tipo',
			'nome', 'nome_en', 'texto', 'class', 'class_tipo', 'template', 'editpage', 
			'template_inserir', 'tabela', 'disparo', 'campos', 'arquivos', 'arquivos_ajuda',
			'arquivos_2', 'arquivos_2_ajuda', 'arquivos_3', 'arquivos_3_ajuda', 'arquivos_4',
			'arquivos_4_ajuda', 'links', 'links_ajuda', 'children', 'mostrar', 'language',
			'menu', 'busca', 'restrito', 'admin', 'editar', 'unico', 'publish_tipo', 'versoes',
			'hits', 'tags', 'tags_tipo', 'tags_registros', 'visualizar', 'ordem', 'log', 'deleted_tipo'
		);
	}
	public function getAttributesCampos() {
		return array();
	}
	public function getAttributesAliases() {
		return array();
	}
	public function getTableName() {
		return $this->db_prefix . '_tipos';
	}
	public function getInterAdminsOrder() {
		if (!$interadminsOrderBy = $this->_getMetadata('interadmins_order')) {
			$campos = $this->getCampos();
			if ($campos) {
				foreach ($campos as $key => $row) {
					if ($row['orderby']) {
						if ($row['orderby'] < 0) $key .= " DESC";
						$tipo_orderby[$row['orderby']] = $key;
					}
				}
				if ($tipo_orderby) {
					ksort($tipo_orderby);
					$tipo_orderby = implode(",", $tipo_orderby);
				}
				$interadminsOrderBy = $tipo_orderby;
			}
			if (!$tipo_orderby) {
				$interadminsOrderBy = 'date_publish DESC';
			}
			$this->_setMetadata('interadmins_order', $interadminsOrderBy);
		}
		return $interadminsOrderBy;
	}
	/**
	 * Returns the table name for this tipo.
	 * 
	 * @return string
	 */
	public function getInterAdminsTableName() {
		global $lang;
		$table = $this->db_prefix .	(($this->tabela) ? '_' . $this->tabela : '');
		if (!isset($this->language)) {
			$this->getFieldsValues('language');
		}
		if ($this->language) {
			$table .= $lang->prefix;
		}
		return $table;
	}
	protected function _setMetadata($varname, $value) {
		self::$_metadata[$this->id_tipo][$varname] = $value;
	}
	protected function _getMetadata($varname) {
		return self::$_metadata[$this->id_tipo][$varname];
	}
	/**
	 * Creates a record with id_tipo, char_key and date_publish filled.
	 * 
	 * @return InterAdmin
	 */
	public function createInterAdmin() {
		$options = array('default_class' => $this->_getDefaultClass());
		$record = InterAdmin::getInstance(0, $options, $this);
		$mostrar = $this->getCamposAlias('char_key');
		$record->$mostrar = 'S';
		$record->date_publish = date('c');
		return $record;
	}
	
}
