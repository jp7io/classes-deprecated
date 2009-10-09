<?php
abstract class InterAdminAbstract {
	protected $_primary_key = 'id';	
	/**
	 * Array of all the attributes with their names as keys and the values of the attributes as values.
	 * @var array 
	 */
	public $attributes = array();
	/**
	 * Used to know if the values were updated through setFieldsValues(). 
	 * Hack for compatibility with legacy sites.
	 * 
	 * @var bool
	 */
	protected $_updated = false;
	protected $_deleted = false;
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
	public function getFieldsValues($fields, $forceAsString = false, $fieldsAlias = false) {
		if ($this->_deleted) {
			throw new Exception('This record has been deleted.');
		}
		if ($fields == '*' || $fields == array('*')) {
			$fields = $this->getAttributesNames();
		}
		// cache
		$fieldsToLoad = array();
		if ($forceAsString || $this->_updated) {
			$fieldsToLoad = $fields;
		} else {
			$fieldsToLoad = array_diff((array) $fields, array_keys($this->attributes));
		}
		// Retrieving data
		if ($fieldsToLoad) {
			$options = array(
				'fields' => (array) $fieldsToLoad,
				'fields_alias' => $fieldsAlias,
				'from' => $this->getTableName() . " AS main",
				'where' => array($this->_primary_key . " = " . intval($this->{$this->_primary_key}))			
			);
			$rs = $this->_executeQuery($options);
			if ($forceAsString) {
				//@todo return $this->_getFieldsValuesAsString($sqlRow, $tipoLanguage);
			} elseif ($row = $rs->FetchNextObj()) {
				$this->_getAttributesFromRow($row, $this); 
			}
		}
		if (is_array($fields)) {
			// returns only the fields requested on $fields
			foreach ($fields as $key => $value) {
				if (is_array($value)) {
					$fields[$key] = $key;
				}
			}
			return (object) array_intersect_key($this->attributes, array_flip($fields));
		} else {
			return $this->attributes[$fields];
		}
	}
	/**
	 * Updates the values into the database table. If this object has no 'id', the data is inserted.
	 * 
	 * @param array $fields_values Array with the values, the keys are the fields names.
	 * @param bool $force_magic_quotes_gpc If TRUE the string will be quoted even if 'magic_quotes_gpc' is not active.
	 * @return void
	 * @todo Verificar necessidade de $force_magic_quotes_gpc no jp7_db_insert
	 */
	public function setFieldsValues($fields_values, $force_magic_quotes_gpc = false) {
		$pk = $this->_primary_key;
		if ($this->$pk) {
			jp7_db_insert($this->getTableName(), $this->_primary_key, $this->$pk, $fields_values, true, $force_magic_quotes_gpc);
		} else {
			$this->$pk = jp7_db_insert($this->getTableName(), $this->_primary_key, 0, $fields_values, true, $force_magic_quotes_gpc);
		}
		$this->_updated = true; // FIXME Hack temporário
	}
	/**
	 * Saves this record.
	 * 
	 * @return void
	 * @todo ver como fazer o log
	 */
	public function save() {
		$pk = $this->_primary_key;
		$valuesToSave = array();
		$aliases = array_flip($this->getAttributesAliases());
		foreach ($this->attributes as $key => $value) {
			$key = ($aliases[$key]) ? $aliases[$key] : $key;
			if (is_object($value)) {
				$valuesToSave[$key] = (string) $value;
			} else {
				$valuesToSave[$key] = $value;
			}
		}
		if ($this->$pk) {
			jp7_db_insert($this->getTableName(), $this->_primary_key, $this->$pk, $valuesToSave);
		} else {
			$this->$pk = jp7_db_insert($this->getTableName(), $this->_primary_key, 0, $valuesToSave);
		}
	}
	/**
	 * Gets an object by its key, which may be its 'id' or 'id_tipo', and then returns it.
	 * 
	 * @param mixed $value Any value.
	 * @param string $field The name of the field.
	 * @param string $campos Value from getCampos().
	 * @param InterAdminAbstract Object which will receive the attribute.
	 * @return mixed The object created by the key or the value itself.
	 */
	protected function _getByForeignKey(&$value, $field, $campo = '', $object) {
		$interAdminClass = $this->_getDefaultClass();
		
		$options = array();
		if (strpos($field, 'select_') === 0) {
			$isMulti = (strpos($field, 'select_multi') === 0);
			$isTipo = in_array($campo['xtra'], array('S', 'ajax_tipos', 'radio_tipos'));
			$tipo = $campo['nome'];
		} elseif (strpos($field, 'special_') === 0 && $campo['xtra']) {
			$isMulti = in_array($campo['xtra'], array('registros_multi', 'tipos_multi'));
			$isTipo = ($campo['xtra'] == 'multi_tipos' || $campo['xtra'] == 'tipos');
		} elseif (strpos($field, 'file_') === 0 && strpos($field, '_text') === false && $value) {
			$class_name = $interAdminClass . 'FieldFile';
			if (!class_exists($class_name)) {
				$class_name = 'InterAdminFieldFile';
			}
			$file = new $class_name($value);
			$file->setParent($object);
			return $file;
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
	 * @param array $options Default array of options. Available keys: fields, fields_alias, from, where, order, group, limit.
	 * @return ADORecordSet
	 */
	protected function _executeQuery($options, $campos = null, $aliases = null) {
		global $jp7_app, $db;
		// Type casting 
		if (is_null($campos)) {
			$campos = $this->getAttributesCampos();
		}
		if (is_null($aliases)) {
			$aliases = $this->getAttributesAliases();
		}
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
		return $rs;
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
		
		$reserved = array(
			'WHERE', 'AND', 'OR', 'ORDER', 'BY', 'GROUP', 'NOT', 'LIKE', 'IS',
			'NULL', 'DESC', 'ASC', 'BETWEEN', 'REGEXP'
		);
		$quoted = '(\'((?<=\\\\)\'|[^\'])*\')';
		$keyword = '\b[a-zA-Z0-9_.]+\b(?![ ]?\()'; // won't match CONCAT() or IN (1,2)
		
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
	 * @param array $aliases     'alias' => 'field'
	 * @param string $table Table alias for the fields.
	 * @return array Revolved $fields.
	 */
	protected function _resolveFieldsAlias(&$options = array(), $campos, $aliases, $table = 'main') {
		$fields = $options['fields'];
		foreach ($fields as $join => $campo) {
			// Com join
			if (is_array($campo)) {
				$nome = ($aliases[$join]) ? $aliases[$join] : $join;
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
			// Com função
			} elseif (strpos($campo, '(') !== false) {
				$fields[$join] = preg_replace('/([\(,][ ]*)/', '\1' . $table . '.', $campo) .
				 	' AS `' . $table . '.' . trim(strtolower(preg_replace('/[\(\),]/', '_', $campo)), '_') . '`';
			// Sem join
			} else {
				$nome = ($aliases[$campo]) ? $aliases[$campo] : $campo;
				if (strpos($nome, 'file_') === 0 && strpos($nome, '_text') === false) {
					$fields[] = $table . '.' . $nome . '_text  AS `' . $campo . '.text`';
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
	 * @return void
	 */
	protected function _getAttributesFromRow($row, $object, $campos = null, $aliases = null) {
		if (is_null($campos)) {
			$campos = $this->getAttributesCampos();
		}
		if (is_null($aliases)) {
			$aliases = $this->getAttributesAliases();
		}
		if ($aliases) {
			$fields = array_flip($aliases);
		}
		
		$attributes = &$object->attributes;
		foreach ($row as $key => $value) {
			list($table, $field) = explode('.', $key);
			if ($table == 'main') {
				$alias = ($aliases[$field]) ? $aliases[$field] : $field;
				$value = $this->_getByForeignKey($value, $field, $campos[$field], $object);
				$attributes[$alias] = $value;
			} else {
				$joinAlias = '';
				$join = ($fields[$table]) ? $fields[$table] : $table;
				$joinTipo = $campos[$join]['nome'];
				if (is_object($joinTipo)) {
					$joinCampos = $joinTipo->getCampos();
					$joinAlias = $joinTipo->getCamposAlias($field);
				}
				$alias = ($aliases && $joinAlias) ? $joinAlias : $field;
				$value = $this->_getByForeignKey($value, $field, $joinCampos[$field], $object);
				if (is_object($attributes[$table])) {
					$attributes[$table]->$alias = $value;
				}
			}
		}
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
	
	/**
	 * Reloads all the attributes.
	 * 
	 * @todo Not implemented yet. Won't work with recursive objects and alias.
	 * @return void
	 */
	public function reload($fields = null) {
		if (is_null($fields)) {
			$fields = array();
			foreach ($this->attributes as $key => $attribute) {
				$fields[] = $key;
			}
		}
		$this->attributes = array();
		$this->getFieldsValues($fields);
	}
	/**
	 * Creates a object of the given Class name with the same attributes.
	 * 
	 * @param object $className
	 * @return InterAdminAbstract An instance of the given Class name.
	 */
	public function becomes($className) {
		$newobject = new $className();
		$newobject->attributes = $this->attributes;
		return $newobject;
	}
	
	/**
	 * Deletes this row from the table.
	 * 
	 * @return 
	 */
	public function delete() {
		global $db;
		$pk = $this->_primary_key;
		if ($this->$pk) {
			$sql = "DELETE FROM " . $this->getTableName() . 
				" WHERE " . $this->_primary_key . " = " . $this->$pk;
		}
		$this->attributes = array();
		$this->_deleted = true;
	}
}
