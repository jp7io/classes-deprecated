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
	protected $_campos;
	protected $_url;
	protected $_parent;
	/**
	 * @param int $id_tipo
	 * @param array $options
	 */
	function __construct($id_tipo = 0, $options = array()) {
		$this->id_tipo = $id_tipo;
		$this->db_prefix = ($options['db_prefix']) ? $options['db_prefix'] : $GLOBALS['db_prefix'];
		if ($options['parentInterAdmin']) $this->parentInterAdmin = $options['parentInterAdmin'];
		if ($options['fields']) $this->getFieldsValues($options['fields']);
	}
	function __toString() {
		return (string) $this->id_tipo;
	}
	function __get($var) {
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
		}
	}
	/**
	 * @return mixed
	 */
	function getFieldsValues($fields) {
		$fieldsValues = jp7_fields_values($this->db_prefix.'_tipos', 'id_tipo', $this->id_tipo, $fields, TRUE);
		foreach ((array) $fieldsValues as $field=>$value) {
			$this->$field = $value;
		}
		if (is_array($fields)) return $fieldsValues;
		elseif ($fields) return $fieldsValues->$fields;
	}
	/**
	 * @return object
	 */
	function getParent($options = array()) {
		if ($this->_parent) return $this->_parent;
		$parent_id_tipo = $this->getFieldsValues(array('parent_id_tipo'))->parent_id_tipo;
		if ($parent_id_tipo) {
			$class_name = ($options['class']) ? $options['class'] : get_class($this); 
			$this->_parent = new $class_name($parent_id_tipo, array('db_prefix' => $this->db_prefix));
			return $this->_parent;
		}
	}
	function setParent($parent) {
		$this->_parent = $parent;
	}
	/**
	 * @return array
	 */
	function getChildren($options = array()){
		global $db;
		global $jp7_app;
		$sql = "SELECT id_tipo" . (($options['fields']) ? ',' . implode(',', (array)$options['fields']) : '') . " FROM " . $this->db_prefix."_tipos" .
		" WHERE parent_id_tipo=".$this->id_tipo . 
		(($options['where']) ? $options['where'] : '') . 
		" ORDER BY " . (($options['order']) ? $options['order'] : 'ordem, nome') .
		(($options['limit']) ? " LIMIT " . $options['limit'] : '');
		if ($jp7_app) $rs = $db->Execute($sql)or die(jp7_debug($db->ErrorMsg(), $sql));
		else $rs = interadmin_query($sql);
		while ($row = $rs->FetchNextObj()) {
			$class_name = ($options['class']) ? $options['class'] : get_class($this); 
			$interAdminTipo = new $class_name($row->id_tipo, array('db_prefix' => $this->db_prefix));
			$interAdminTipo->setParent($this);
			foreach((array)$options['fields'] as $field){
				$interAdminTipo->$field = $row->$field;
			}
			$interAdminTipos[] = $interAdminTipo;
		}
		$rs->Close();
		return $interAdminTipos;
	}
	/**
	 * @return array
	 */
	function getChildrenByModel($model_id_tipo, $options = array()) {
		$options['where'] .= " AND model_id_tipo=" . $model_id_tipo;
		return $this->getChildren($options);
	}
	/**
	 * @return array
	 */
	function getInterAdmins($options = array()) {
		global $db;
		global $lang;
		global $jp7_app;
		$interAdmins = array();
		//if ($options['fields_alias']) {
			$campos = $this->getCampos();
			$table = ($this->getModel()->getFieldsValues('tabela')) ? '_' . $this->getModel()->getFieldsValues('tabela') : '';
		//}
		if ($options['fields'] == '*') {
			$options['fields'] = array();
			$invalid_fields = array('tit', 'func');
			$all_fields = array_keys($campos);
			foreach ($all_fields as $field) {
				$field_arr = explode('_', $field);
				if (!in_array($field_arr[0], $invalid_fields)) $options['fields'][] = $field;
			}
		}
		$sql = "SELECT id" . (($options['fields']) ? ',' . implode(',', (array)$options['fields']) : '') . 
		" FROM " . $this->db_prefix . $table . (($this->getFieldsValues('language')) ? $lang->prefix : '') .
		" WHERE id_tipo=" . $this->id_tipo.
		(($this->parentInterAdmin) ? " AND parent_id=" . $this->parentInterAdmin->id : '') .
		(($options['where']) ? $options['where'] : '') .
		" ORDER BY " . (($options['order']) ? $options['order'] . ',' : '') . $this->interadminsOrderby .
		(($options['limit']) ? " LIMIT " . $options['limit'] : '');
		if ($jp7_app) $rs = $db->Execute($sql)or die(jp7_debug($db->ErrorMsg(), $sql));
		else $rs = interadmin_query($sql);
		while ($row = $rs->FetchNextObj()) {
			$class_name = ($options['class']) ? $options['class'] : 'InterAdmin'; 
			$interAdmin = new $class_name($row->id, array('db_prefix' => $this->db_prefix, 'table' => $this->tabela));
			$interAdmin->setTipo($this);
			if ($this->parentInterAdmin) $interAdmin->setParent($this->parentInterAdmin);
			foreach((array)$options['fields'] as $field){
				$alias = ($options['fields_alias']) ? $this->getCamposAlias($field) : $field;
				if (strpos($field, 'select_') === 0) {
					if (strpos($field, 'select_multi') === 0) {
						$value_arr = explode(',', $row->$field);
						if (!$value_arr[0]) $value_arr = array();
						foreach ($value_arr as $key2 => $value2) {
							if ($campos[$field]['xtra'] === 'S') {
								$value_arr[$key2] = new InterAdminTipo($value2);
							} else {
								$value_arr[$key2] = new InterAdmin($value2);
							}
						}
						$row->$field = $value_arr;
					} elseif($row->$field && is_numeric($row->$field)) {
						if ($campos[$field]['xtra'] === 'S') {
							$row->$field = new InterAdminTipo($row->$field);
						} else {
							$row->$field = new InterAdmin($row->$field);
						}
					}
				}
				$interAdmin->$alias = $row->$field;
			}
			$interAdmins[] = $interAdmin;
		}
		$rs->Close();
		return $interAdmins;
	}
	/**
	 * @return array
	 */
	function getFirstInterAdmin($options = array()) {
		$options['limit'] = 1;
		$interAdmin = $this->getInterAdmins($options);
		return $interAdmin[0];
	}
	/**
	 * @return ?
	 */
	function getModel() {
		$model_id_tipo = $this->getFieldsValues('model_id_tipo');
		if ($model_id_tipo) {
			$model = new InterAdminTipo($model_id_tipo);
			return $model->getModel();
		} else {
			return $this;
		}
	}
	/**
	 * @return ?
	 */
	function getCampos() {
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
	/**
	 * @return string
	 */
	function getCamposAlias($field) {
		$campos = $this->getCampos();
		if ($campos[$field]['nome_id']) return $campos[$field]['nome_id'];
		$alias = $campos[$field]['nome'];
		if (is_object($alias)) $alias = jp7_fields_values($this->db_prefix . '_tipos', 'id_tipo', $alias->id_tipo, 'nome');
		$alias = ($alias) ? toId($alias) : $field;
		return $alias;
	}
	/**
	 * @return string
	 */
	function getStringValue($simple = FALSE) {
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
	function getUrl() {
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
	function getTreePath() {
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