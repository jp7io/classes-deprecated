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
 * @package InterAdminTipo
 */
class InterAdminTipo{
	public $id_tipo;
	public $db_prefix;
	protected $_url;
	protected $_campos;
	protected $_parent;
	/**
	 * Public Constructor.
	 * 
	 * @param int $id_tipo
	 * @param array $options
	 */
	public function __construct($id_tipo = 0, $options = array()) {
		$this->id_tipo = $id_tipo;
		$this->db_prefix = ($options['db_prefix']) ? $options['db_prefix'] : $GLOBALS['db_prefix'];
		if ($options['fields']) $this->getFieldsValues($options['fields']);
	}
	/**
	 * Returns an InterAdminTipo instance. If $options['class'] is passed, 
	 * it will be returned an object of the given class, otherwise it will search 
	 * on the database which class to instantiate.
	 *
	 * @param int $id_tipo
	 * @param array $options
	 * @return InterAdminTipo
	 */
	public static function getInstance($id_tipo, $options = array()){
		if ($options['class']) {
			$class_name = (class_exists($options['class'])) ? $options['class'] : 'InterAdminTipo';
		} else {
			$instance = new InterAdminTipo($id_tipo, array_merge($options, array('fields' => array('model_id_tipo', 'class_tipo'))));
			if ($instance->class_tipo) $class_name = $instance->class_tipo;
			else $class_name = jp7_fields_values($instance->db_prefix . '_tipos', 'id_tipo', $instance->model_id_tipo, 'class_tipo');
			if (!class_exists($class_name)) {
				if ($options['fields']) $instance->getFieldsValues($options['fields']);
				return $instance;
			}
		}
		return new $class_name($id_tipo, $options);
	}
	/**
	 * Returns the string value of its $id_tipo.
	 *
	 * @return string
	 */
	public function __toString() {
		return (string) $this->id_tipo;
	}
	public function __get($var) {
		if ($var == 'interadminsOrderby') {
			$campos = $this->getCampos();
			if ($campos) {
				foreach ($campos as $key=>$row) {
					if ($row['orderby'])$tipo_orderby[$row['orderby']] = $key;
				}
				if ($tipo_orderby) {
					ksort($tipo_orderby);
					$tipo_orderby = implode(",", $tipo_orderby);
				}
				$this->$var = $tipo_orderby;
			}
			if (!count($tipo_orderby)) {
				$this->$var = 'date_publish DESC';
			}
			return $this->$var;
		} elseif ($var == 'class') {
			return ($this->getFieldsValues('class')) ? $this->class : $this->getModel()->getFieldsValues('class');	
		}
	}
	/**
	 * @return mixed
	 */
	public function getFieldsValues($fields) {
		$fieldsValues = jp7_fields_values($this->db_prefix.'_tipos', 'id_tipo', $this->id_tipo, $fields, TRUE);
		foreach ((array) $fieldsValues as $field=>$value) {
			$this->$field = $value;
		}
		if (is_array($fields)) return $fieldsValues;
		elseif ($fields) return $fieldsValues->$fields;
	}
	/**
	 * @return InterAdmin
	 */
	public function getParent($options = array()) {
		if ($this->_parent) return $this->_parent;
		if ($this->parent_id_tipo || $this->getFieldsValues('parent_id_tipo')) {
			return $this->_parent = InterAdminTipo::getInstance($this->parent_id_tipo, $options);
		}
	}
	public function setParent($parent) {
		$this->_parent = $parent;
	}
	/**
	 * @return array
	 */
	public function getChildren($options = array()){
		global $db;
		global $jp7_app;
		$options['fields'] = array_merge(array('id_tipo'), (array) $options['fields']);
		$options['from'] = $this->db_prefix . "_tipos AS main";
		$options['where'] = "parent_id_tipo = " . $this->id_tipo . $options['where'];
	 	if (!$options['order']) $options['order'] = 'ordem, nome';

		$rs = $this->executeQuery($options);
		
		while ($row = $rs->FetchNextObj()) {
			$interAdminTipo = InterAdminTipo::getInstance($row->id_tipo, array(
				'db_prefix' => $this->db_prefix,
				'class' => $options['class']
			));
			$interAdminTipo->setParent($this);
			$this->putOrmData($interAdminTipo, $row, $options);
			$interAdminTipos[] = $interAdminTipo;
		}
		$rs->Close();
		return $interAdminTipos;
	}
	/**
	 * @return array
	 */
	public function getChildrenByModel($model_id_tipo, $options = array()) {
		$options['where'] .= " AND main.model_id_tipo = " . $model_id_tipo;
		return $this->getChildren($options);
	}
	/**
	 * @return array
	 */
	public function getInterAdmins($options = array()) {
		global $lang;
		$campos = $this->getCampos();
		$model = $this->getModel();
		$table = ($model->getFieldsValues('tabela')) ? '_' . $model->tabela : '';

		if ($options['fields'] == '*') $options['fields'] = $this->getAllFieldsNames();
		$options['fields'] = array_merge(array('id'), (array) $options['fields']);
		$options['from'] = $this->db_prefix . $table . (($this->getFieldsValues('language')) ? $lang->prefix : '') . " AS main";
		$options['where'] = "main.id_tipo = " . $this->id_tipo . $options['where'];
		if ($this->_parent && $this->_parent instanceof InterAdmin) $options['where'] .= " AND main.parent_id = " . $this->_parent->id;	
		$options['order'] = (($options['order']) ? $options['order'] . ',' : '') . $this->interadminsOrderby;
		
		$rs = $this->executeQuery($options);
		
		$interAdmins = array();
		while ($row = $rs->FetchNextObj()) {
			$class_name = ($options['class']) ? $options['class'] : $this->class;
			$interAdmin = InterAdmin::getInstance($row->id, array(
				'db_prefix' => $this->db_prefix,
				'table' => $this->tabela,
				'class' => $class_name
			));
			$interAdmin->setTipo($this);
			
			if ($this->_parent && $this->_parent instanceof InterAdmin) $interAdmin->setParent($this->_parent);
			
			$this->putOrmData($interAdmin, $row, $options);
			
			$interAdmins[] = $interAdmin;
		}
		$rs->Close();
		return $interAdmins;
	}
	/**
	 * @return InterAdmin
	 */
	public function getFirstInterAdmin($options = array()) {
		$options['limit'] = 1;
		$interAdmin = $this->getInterAdmins($options);
		return $interAdmin[0];
	}
	/**
	 * Returns the model identified by model_id_tipo, or the object itself if it has no model.
	 *
	 * @param array $options
	 * @return InterAdminTipo
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
	 * @return array
	 */
	public function getCampos() {
		if ($this->_campos) return $this->_campos;
		$model = $this->getModel();
		$campos = $model->getFieldsValues('campos');
		unset($model->campos);
		$campos_parameters = array('tipo', 'nome', 'ajuda', 'tamanho', 'obrigatorio', 'separador', 'xtra', 'lista', 'orderby', 'combo', 'readonly', 'form', 'label', 'permissoes', 'default', 'nome_id');
		$campos	= split('{;}', $campos);
		$A = array();
		for ($i = 0; $i < count($campos); $i++) {
			$parameters = split("{,}", $campos[$i]);
			if ($parameters[0]) {
				$A[$parameters[0]]['ordem'] = ($i+1);
				$isSelect = (strpos($parameters[0], 'select_') !== FALSE);
				for ($j = 0 ; $j < count($parameters); $j++) {
					$A[$parameters[0]][$campos_parameters[$j]] = $parameters[$j];
				}
				if ($isSelect && $A[$parameters[0]]['nome'] != 'all') {
					$id_tipo = $A[$parameters[0]]['nome'];
					$Cadastro_r = new InterAdminTipo($id_tipo);
					$A[$parameters[0]]['nome'] = $Cadastro_r;
					//jp7_print_r($parameters[0]);
					//jp7_print_r($A[$parameters[0]]['nome']);
				}
			}
		}
		return $this->_campos = $A;
		//return interadmin_tipos_campos($this->getFieldsValues('campos'));
	}
	public function getAllFieldsNames(){
		$fields = array();
		$invalid_fields = array('tit', 'func');
		$all_fields = array_keys($this->getCampos());
		foreach ($all_fields as $field) {
			$field_arr = explode('_', $field);
			if (!in_array($field_arr[0], $invalid_fields)) $fields[] = $field;
		}
		return $fields;
	}
	public function putOrmData(&$object, &$row, $options){
		$campos = $this->getCampos();
		$joinCount = 0;
		foreach((array)$options['fields'] as $join => $field){
			if (is_array($field)) {
				$field = $join;
				$joinCount++;
			}
			$alias = ($options['fields_alias']) ? $this->getCamposAlias($field) : $field;
			$object->$alias = $this->getByForeignKey($row->$field, $field, $campos[$field]['xtra']);

			if (is_object($object->$alias) && is_array($options['fields'][$field])) {
				foreach($options['fields'][$field] as $joinField) {
					$joinAlias = ($options['fields_alias']) ? $campos[$field]['nome']->getCamposAlias($joinField) : $joinField;
					$joinCampos = $campos[$field]['nome']->getCampos();
					$rowField = 'join' . $joinCount . '_' . $joinField;
					$object->$alias->$joinAlias = $this->getByForeignKey($row->$rowField, $joinField, $joinCampos[$joinField]['xtra']);
				}
			}
		}
	}
	
	public function getByForeignKey(&$value, $field, $xtra = ''){
		if (strpos($field, 'select_') === 0) {
			if (strpos($field, 'select_multi') === 0) {
				$value_arr = explode(',', $value);
				if (!$value_arr[0]) $value_arr = array();
				foreach ($value_arr as $key2 => $value2) {
					if ($xtra === 'S') {
						$value_arr[$key2] = InterAdminTipo::getInstance($value2);
					} else {
						$value_arr[$key2] = InterAdmin::getInstance($value2);
					}
				}
				$value = $value_arr;
			} elseif($value && is_numeric($value)) {
				if ($xtra === 'S') {
					$value = InterAdminTipo::getInstance($value);
				} else {
					$value = InterAdmin::getInstance($value);
				}
			}
		}
		return $value;
	}
	
	public function executeQuery($options){
		global $jp7_app, $db, $lang;
		$campos = $this->getCampos();
		// Join
		$joinsCount = 0;
		if (!is_array($options['from'])) $options['from'] = (array) $options['from'];
		foreach($options['fields'] as $key => $fields){
			if (is_array($fields)) {
				$join = 'join' . ++$joinsCount;
				$options['fields'][$key] = 'main.' . $key;
				$joinModel = $campos[$key]['nome']->getModel();
				if ($campos[$key]['xtra'] == 'S') {
					$options['from'][] = $this->db_prefix . "_tipos" .
						" AS " . $join . " ON "  . $options['fields'][$key] . " = " . $join . ".id_tipo";
				} else {
					$options['from'][] = $this->db_prefix .
						(($joinModel->getFieldsValues('tabela')) ? '_' . $joinModel->tabela : '') .
						(($campos[$key]['nome']->getFieldsValues('language')) ? $lang->prefix : '') .
						" AS " . $join . " ON "  . $options['fields'][$key] . " = " . $join . ".id";
				}
				
				foreach($fields as $joinField) {
					$options['fields'][] = $join . '.' . $joinField . " AS " . $join . '_' . $joinField;
				}
			} else {
				$options['fields'][$key] = 'main.' . $fields;
			}
		}
		
		// Order Fix
		$order_arr = jp7_explode(',', $options['order']);
		foreach ($order_arr as $key => &$value) {
			if (strtok($value, '.()') == $str) $value = 'main.' . $value;
		}
		$options['order'] = implode(',', $order_arr);
		
		// Sql
		$sql = "SELECT " . (($options['fields']) ? implode(',', $options['fields']) : '') .
			" FROM " . implode(' LEFT JOIN ', $options['from']) .
			" WHERE " . $options['where'] .
			(($options['order']) ? " ORDER BY " . $options['order'] : '') .
			(($options['limit']) ? " LIMIT " . $options['limit'] : '');
		
		if ($jp7_app) $rs = $db->Execute($sql) or die(jp7_debug($db->ErrorMsg(), $sql));
		else $rs = interadmin_query($sql);
		return $rs;
	}
	
	/**
	 * @return string
	 */
	public function getCamposAlias($field) {
		$campos = $this->getCampos();
		if ($campos[$field]['nome_id']) return $campos[$field]['nome_id'];
		$alias = $campos[$field]['nome'];
		if (is_object($alias)) $alias = ($alias->nome) ? $alias->nome : $alias->getFieldsValues('nome');
		$alias = ($alias) ? toId($alias) : $field;
		return $alias;
	}
	/**
	 * @return string
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
	 * @return string
	 */
	public function getUrl() {
		if ($this->_url) return $this->_url;
		global $c_url, $c_cliente_url, $c_cliente_url_path, $implicit_parents_names, $jp7_app, $seo, $lang;
		$url = '';
		$url_arr = '';
		$parent = $this;
		while ($parent) {
			if ($seo) {
				if (!in_array($parent->getFieldsValues('nome'), (array)$implicit_parents_names)) $url_arr[] = toSeo($parent->getFieldsValues('nome'));
			} else {
				$url_arr[] = toId($parent->getFieldsValues('nome'));
			}
			$parent = $parent->getParent();
		}
		$url_arr = array_reverse((array)$url_arr);

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
}
?>