<?
/**
 * Instancia registros da tabela interadmin_cliente_tipos
 *
 */
class InterAdminTipo{
	public $id_tipo;
	protected $campos;
	protected $url;
	protected $parent;
	/**
	 * @param int $id_tipo
	 * @param varchar $_db_prefix
	 */
	function __construct($id_tipo, $options = NULL) {
		$this->id_tipo = $id_tipo;
		$this->db_prefix = ($options['db_prefix']) ? $options['db_prefix'] : $GLOBALS['db_prefix'];
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
					$tipo_orderby=implode(",", $tipo_orderby);
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
		if (is_array($fields)) return $fieldsValues;
		elseif ($fields) return $fieldsValues->$fields;
	}
	/**
	 * @return object
	 */
	function getParent() {
		if ($this->parent) return $this->parent;
		$parent = $this->getFieldsValues(array('parent_id_tipo'))->parent_id_tipo;
		if ($parent) {
			$class_name = get_class($this);
			$this->parent = new $class_name($parent, array('db_prefix' => $this->db_prefix));
			return $this->parent;
		}
	}
	/**
	 * @return array
	 */
	function getChildren($options = NULL){
		global $db;
		global $jp7_app;
		$sql = "SELECT id_tipo" . (($options['fields']) ? ',' . implode(',', (array)$options['fields']) : '') . " FROM ".$this->db_prefix."_tipos".
		" WHERE parent_id_tipo=".$this->id_tipo . 
		(($options['where']) ? $options['where'] : '') . 
		" ORDER BY " . (($options['order']) ? $options['order'] : 'ordem, nome') .
		(($options['limit']) ? " LIMIT " . $options['limit'] : '');
		if ($jp7_app) $rs = $db->Execute($sql)or die(jp7_debug($db->ErrorMsg(), $sql));
		else $rs = interadmin_query($sql);
		while ($row = $rs->FetchNextObj()) {
			$class_name = get_class($this);
			$interadmintipo = new $class_name($row->id_tipo, array('db_prefix' => $this->db_prefix));
			$interadmintipo->parent = $this;
			foreach((array)$options['fields'] as $field){
				$interadmintipo->$field = $row->$field;
			}
			$interadminsTipos[] = $interadmintipo;
		}
		$rs->Close();
		return $interadminsTipos;
	}
	/**
	 * @return array
	 */
	function getChildrenByModel($model_id_tipo){
		$options['where'] = " AND model_id_tipo=".$model_id_tipo;
		return $this->getChildren($options);
	}
	/**
	 * @return array
	 */
	function getInterAdmins($options = NULL) {
		global $db;
		global $lang;
		global $jp7_app;
		$interadmins = array();
		//if ($options['fields_alias']) {
			$campos = $this->getCampos();
		//}
		if ($options['fields'] == '*') {
			$options['fields'] = array();
			$invalid_fields = array('tit', 'func', 'special');
			$all_fields = array_keys($campos);
			foreach ($all_fields as $field) {
				$field_arr = explode('_', $field);
				if (!in_array($field_arr[0], $invalid_fields)) $options['fields'][] = $field;
			}
		}
		$sql = "SELECT id" . (($options['fields']) ? ',' . implode(',', (array)$options['fields']) : '') . 
		" FROM " . $this->db_prefix . (($this->getFieldsValues('language')) ? $lang->prefix : '') .
		" WHERE id_tipo=" . $this->id_tipo.
		(($options['where']) ? $options['where'] : '') .
		" ORDER BY " . (($options['order']) ? $options['order'] . ',' : '') . $this->interadminsOrderby .
		(($options['limit']) ? " LIMIT " . $options['limit'] : '');
		if ($jp7_app) $rs = $db->Execute($sql)or die(jp7_debug($db->ErrorMsg(), $sql));
		else $rs = interadmin_query($sql);
		while ($row = $rs->FetchNextObj()) {
			$interadmin = new InterAdmin($row->id, array('db_prefix' => $this->db_prefix));
			$interadmin->id_tipo = $this->id_tipo;
			$interadmin->tipo = $this;
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
				$interadmin->$alias = $row->$field;
			}
			$interadmins[] = $interadmin;
		}
		$rs->Close();
		return $interadmins;
	}
	/**
	 * @return array
	 */
	function getFirstInterAdmin($options = NULL) {
		$options['limit'] = 1;
		$interadmin = $this->getInterAdmins($options);
		return $interadmin[0];
	}
	/**
	 * @return ?
	 */
	function getModel() {
		$model = $this->getFieldsValues('model_id_tipo');
		if ($model) {
			$model_obj = new InterAdminTipo($model);
			return $model_obj->getModel();
		} else {
			return $this;
		}
	}
	/**
	 * @return ?
	 */
	function getCampos() {
		if ($this->campos) return $this->campos;
		$model = $this->getModel();
		$campos = $model->getFieldsValues('campos');
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
		return $this->campos = $A;
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
		if ($this->url) return $this->url;
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
		
		return $this->url = $url;
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