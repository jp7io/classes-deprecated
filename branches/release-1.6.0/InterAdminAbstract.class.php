<?php
abstract class InterAdminAbstract {
	protected $_primary_key = 'id';	
	/**
	 * Array of all the attributes with their names as keys and the values of the attributes as values.
	 * @var array 
	 */
	public $attributes = array();
	/**
	 * Magic get acessor.
	 * 
	 * @param string $attributeName
	 * @return mixed
	 */
	public function __get($attributeName) {
		return $this->attributes[$attributeName];
	}
	/**
	 * Magic set acessor.
	 * 
	 * @param string $attributeName
	 * @param string $attributeValue
	 * @return void
	 */
	public function __set($attributeName, $attributeValue) {
		$this->attributes[$attributeName] = $attributeValue;
	}
	/**
	 * Magic unset acessor.
	 * 
	 * @param string $attributeName
	 * @return void
	 */
	public function __unset($attributeName) {
		unset($this->attributes[$attributeName]);
	}
	/**
	 * Magic isset acessor.
	 * 
	 * @param string $attributeName
	 * @return bool
	 */
	public function __isset($attributeName) {
		return isset($this->attributes[$attributeName]);
	}
	/** 
	 * Gets values from this record on the database.
	 *
	 * @param array|string $fields Array of fields or name of the field to be retrieved, '*' to get all the fields.
	 * @param bool $forceAsString Gets the string value for fields referencing to another InterAdmin ID (fields started by "select_").
	 * @param bool $fieldsAlias If <tt>TRUE</tt> the names of the fields are replaced by the Alias that were inserted on the InterAdmin.
	 * @return mixed If $fields is an array an object will be returned, otherwise it will return the value retrieved.
	 * @todo (FIXME - Multiple languages) When there is no id_tipo yet, the function is unable to decide which language table it should use.
	 */
	public function getFieldsValues($fields, $forceAsString = false, $fieldsAlias = false, $reloadValues = false) {   
		if ($fields == '*' || $fields == array('*')) {
			$fields = $this->getAttributesNames();
		}
		// cache
		$fieldsToLoad = array();
		if ($reloadValues || $forceAsString) {
			$fieldsToLoad = $fields;
		} else {
			foreach ((array) $fields as $key => $field) {
				if (is_array($field) || !isset($this->$field)) {
					$fieldsToLoad[$key] = $field;		
				}
			}
		}
		// Retrieving data
		if ($fieldsToLoad) {
			$options = array(
				'fields' => (array) $fieldsToLoad,
				'fields_alias' => $fieldsAlias,
				'from' => $this->getTableName() . " AS main",
				'where' => array($this->_primary_key . " = " . intval($this->{$this->_primary_key}))			
			);
			$attributes = $this->_executeQuery($options, $this->getAttributesCampos(), $this->getAttributesAliases());
			if ($forceAsString) {
				//@todo return $this->_getFieldsValuesAsString($sqlRow, $tipoLanguage);
			} elseif ($attributes[0]) {
				$this->attributes = array_merge($this->attributes, $attributes[0]); 
			}
		}
		// @todo return only the fields requested on $fields
		if (is_array($fields)) {
			return $this->attributes;
		} else {
			return $this->attributes[$fields];
		}
	}
	/**
	 * Gets an object by its key, which may be its 'id' or 'id_tipo', and then returns it.
	 * 
	 * @param mixed $value Any value.
	 * @param string $field The name of the field.
	 * @param string $campos Value from getCampos().
	 * @return mixed The object created by the key or the value itself.
	 */
	protected function _getByForeignKey(&$value, $field, $campo = ''){
		$interAdminClass = $this->_getDefaultClass();
		
		$options = array();
		if (strpos($field, 'select_') === 0) {
			$isMulti = (strpos($field, 'select_multi') === 0);
			$isTipo = in_array($campo['xtra'], array('S', 'ajax_tipos', 'radio_tipos'));
			$tipo = $campo['nome'];
		} elseif (strpos($field, 'special_') === 0 && $campo['xtra']) {
			$isMulti = in_array($campo['xtra'], array('registros_multi', 'tipos_multi'));
			$isTipo = ($campo['xtra'] == 'multi_tipos' || $campo['xtra'] == 'tipos');
		}
		
		$options['default_class'] =  $interAdminClass . (($isTipo) ? 'Tipo' : '');
		
		if (isset($isMulti)) {
			if ($isMulti) {
				$value_arr = explode(',', $value);
				if (!$value_arr[0]) $value_arr = array();
				foreach ($value_arr as $key2 => $value2) {
					if ($isTipo) {
						$value_arr[$key2] = InterAdminTipo::getInstance($value2, $options);
					} else {
						$value_arr[$key2] = InterAdmin::getInstance($value2, $options, $tipo);
					}
				}
				$value = $value_arr;
			} elseif ($value && is_numeric($value)) {
				if ($isTipo) {
					$value = InterAdminTipo::getInstance($value, $options);
				} else {
					$value = InterAdmin::getInstance($value, $options, $tipo);
				}
			}
		}
		
		return $value;
	}
	/**
	 * Executes a SQL Query based on the values passed by $options.
	 * 
	 * @param array $options Default array of options. Available keys: count, fields, fields_alias, from, where, order, group, limit.
	 * @return array Array of attributes.
	 */
	protected function _executeQuery($options, $campos = array(), $aliases = array()) {
		$attributes = array();
		if (!$options['fields']) {
		    return $attributes;
		}
		global $jp7_app, $db;
		// Type casting 
		if (!is_array($options['from'])) {
    		$options['from'] = (array) $options['from'];
		}
		if (!is_array($options['where'])) {
		    $options['where'] = (array) $options['where'];
		}
		$options['where'] = implode(' AND ', $options['where']);
		if (!is_array($options['fields'])) {
			$options['fields'] = (array) $options['fields'];
		}
		if ($options['fields_alias']) {
			$aliases = array_flip($aliases);	
		} else {
			$aliases = array();
		}
		// Resolve Alias and Joins for 'fields' and 'from'
		$this->_resolveFieldsAlias($options, $campos, $aliases);
		// Resolve Alias and Joins for 'where', 'group' and 'order';
		$clauses = $this->_resolveSqlClausesAlias($options, $campos, $aliases);
		// Count @todo Ver se funciona
		if ($options['count']) {
		    $options['fields'] = (array) $options['count'];
		}
		
		// Sql
		$sql = "SELECT " . implode(',', $options['fields']) .
			" FROM " . implode(' LEFT JOIN ', $options['from']) .
			$clauses .
			(($options['limit']) ? " LIMIT " . $options['limit'] : '');
	
		if ($jp7_app) {
			$rs = $db->Execute($sql) or die(jp7_debug($db->ErrorMsg(), $sql));
		} else {
			$rs = interadmin_query($sql);
		}
		while ($row = $rs->FetchNextObj()) {
			$attributes[] = $this->_getAttributesFromRow($row, $campos, $aliases);
		}
		
		return $attributes;
	}
	/**
	 * Resolves the aliases on clause using regex
	 * 
	 * @param string $clause
	 * @return 
	 */
	protected function _resolveSqlClausesAlias(&$options = array(), $campos, $aliases) {
		$clause = " WHERE " . $options['where'] .
			(($options['group']) ? " GROUP BY " . $options['group'] : '') .
			(($options['order']) ? " ORDER BY " . $options['order'] : '');
		
		$reserved = array('WHERE', 'AND', 'ORDER', 'BY', 'GROUP', 'OR', 'IS', 'NULL', 'BETWEEN', 'NOT', 'LIKE', 'DESC', 'ASC');
		$quoted = '(\'((?<=\\\\)\'|[^\'])*\')';
		$keyword = '\b[a-zA-Z0-9_.]+\b(?!\()';
		
		$offset = 0;
		
		while($retorno = preg_match('/(' . $quoted . '|' . $keyword . ')/', $clause, $matches, PREG_OFFSET_CAPTURE, $offset)) {
			list($termo, $pos) = $matches[1];
			if (!is_numeric($termo) && !in_array($termo, $reserved) && $termo[0] != "'") {
				$len = strlen($termo);
				$table = 'main';
				if (strpos($termo, '.') !== false) {
					list($table, $termo) = explode('.', $termo);
				}
				if ($table != 'main') {
					$joinNome = ($aliases[$table]) ? $aliases[$table] : $table;
					// Permite utilizar relacionamentos no where sem ter usado o campo no fields
					if (!in_array($table, (array) $options['from_alias'])) {
						$this->_addJoinAlias($options, $table, $joinNome, $campos);
					}
					$joinAliases = array_flip($campos[$joinNome]['nome']->getCamposAlias());
					$campo = ($joinAliases[$termo]) ? $joinAliases[$termo] : $termo;
				} else {
					$campo = ($aliases[$termo]) ? $aliases[$termo] : $termo;
				}
				$termo = $table . '.' . $campo;
				$clause = substr_replace($clause, $termo, $pos, $len);
			}
			$offset = $pos + strlen($termo);
		}
		return $clause;
	}
	/**
	 * Resolves Aliases on $options fields.
	 * 
	 * @param array $options Same syntax as $options
	 * @param array $campos 
	 * @param array $aliases
	 * @param string $table Table alias for the fields.
	 * @return array Revolved $fields.
	 */
	protected function _resolveFieldsAlias(&$options = array(), $campos, $aliases, $table = 'main') {
		$fields = $options['fields'];
		foreach ($fields as $join => $campo) {
			// Com join
			if (is_array($campo)) {
				$nome = $aliases[$join];
				if ($nome) {
					$fields[] = $table . '.' . $nome . ' AS `' . $table . '.' . $nome . '`';
					// Join e Recursividade
					$this->_addJoinAlias($options, $join, $nome, $campos);
					$joinTipo = $campos[$nome]['nome'];
					if ($fields[$join] == array('*')) {
						$fields[$join] = array_keys($joinTipo->getCampos());
					}
					$joinOptions = array(
						'fields' => $fields[$join],
						'fields_alias' => $options['fields_alias']
					);
					$this->_resolveFieldsAlias($joinOptions, $joinTipo->getCampos(), array_flip($joinTipo->getCamposAlias()), $join);
					$fields = array_merge($fields, $joinOptions['fields']);
					unset($fields[$join]);
				}
			// Sem join
			} else {
				$nome = ($aliases[$campo]) ? $aliases[$campo] : $campo;
				if (strpos($nome, 'file_') === 0 && strpos($nome, '_text') === false) {
					$fields[] = $table . '.' . $nome . '_text  AS `' . $table . '.' . $nome . '_text`';
				}			
				$fields[$join] = $table . '.' . $nome . ' AS `' . $table . '.' . $nome . '`';
			}
		}
		$options['fields'] = $fields;
	}
	/**
	 * Helper function to add a join.
	 * 
	 * @return void 
	 */
	protected function _addJoinAlias(&$options = array(), $alias, $nome, $campos, $table = 'main') {
		$joinTipo = $campos[$nome]['nome'];
		if (!is_object($joinTipo)) {
			throw new Exception('The field "' . $alias . '" cannot be used as a join on $options.');
		}
		$options['from_alias'][] = $alias; // Used as cache when resolving Where
		if ($campos[$nome]['xtra'] == 'S') { // @todo testar
            $options['from'][] = $this->db_prefix . '_tipos' .
                ' AS ' . $alias . ' ON '  . $table . '.' . $nome . ' = ' . $alias . '.id_tipo';
        } else {
            $options['from'][] = $joinTipo->getInterAdminsTableName() .
                ' AS ' . $alias . ' ON '  . $table . '.' . $nome . ' = ' . $alias . '.id';
        }
	}
	/**
	 * Associates the values on a SQL RecordSet with the fields and insert them on the attributes array.
	 * 
	 * @param array $row Row of a SQL RecordSet.
	 * @param bool $fieldsAlias
	 * @param array $attributes If not provided it will populate an empty array.
	 * @return $attributes
	 */
	protected function _getAttributesFromRow($row, $campos, $aliases) {
		if ($aliases) {
			$fields = $aliases;
			$aliases = array_flip($aliases);
		}
		foreach ($row as $key => $value) {
			list($table, $field) = explode('.', $key);
			if ($table == 'main') {
				$alias = ($aliases[$field]) ? $aliases[$field] : $field;
				if ($aliases && $field == 'select_2') {
					$GLOBALS['bla'] = true;	
				}
				$value = $this->_getByForeignKey($value, $field, $campos[$field]);
				$attributes[$alias] = $value;
			} else {
				$join = ($fields[$table]) ? $fields[$table] : $join;
				$joinTipo = $campos[$join]['nome'];
				$joinCampos = $joinTipo->getCampos();
				$alias = ($aliases && $joinTipo->getCamposAlias($field)) ? $joinTipo->getCamposAlias($field) : $field;
				$value = $this->_getByForeignKey($value, $field, $joinCampos[$field]);
				$attributes[$table]->$alias = $value;
			}
		}
		return $attributes;
	}
	
	protected function _getDefaultClass() {
		$obj = $this;
		if ($obj instanceof InterAdmin) {
			$obj = $obj->getTipo();
		}
		return constant(get_class($obj) . '::DEFAULT_CLASS');
	}
	abstract function getAttributesCampos();
	abstract function getAttributesNames();
	abstract function getAttributesAliases();
	abstract function getTableName();
}
