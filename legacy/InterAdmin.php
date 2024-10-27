<?php

use Jp7\Interadmin\Record;
use Jp7\Interadmin\RecordAbstract;
use Jp7\Interadmin\FileField;
use Illuminate\Support\Collection;

/**
 * JP7's PHP Functions.
 *
 * Contains the main custom functions and classes.
 *
 * @author JP7
 * @copyright Copyright 2002-2008 JP7 (http://jp7.com.br)
 *
 * @category Jp7
 */

/**
 * Class which represents records on the table interadmin_{client name}.
 *
 * @deprecated use Record instead
 */
class InterAdmin extends Record implements InterAdminAbstract
{
    const DEFAULT_NAMESPACE = '';

    /**
     * Sets only the editable attributes, prevents the user from setting $type_id, for example.
     *
     * @param array $attributes
     */
    public function setAttributesSafely(array $attributes)
    {
        $editableFields = array_flip($this->getAttributesAliases());
        $filteredAttributes = array_intersect_key($attributes, $editableFields);
        return $this->setAttributes($filteredAttributes);
    }

    /**
     * Sets this object´s attributes with the given array keys and values.
     *
     * @param array $attributes
     */
    public function setAttributes(array $attributes)
    {
        foreach ($attributes as $key => $value) {
            $this->$key = $value;
        }
    }

    /**
     * Magic method calls.
     *
     * Available magic methods:
     * - create{Child}(array $attributes = array())
     * - get{Children}(array $options = array())
     * - getFirst{Child}(array $options = array())
     * - get{Child}ById(int $id, array $options = array())
     * - get{Child}ByIdString(int $id, array $options = array())
     * - delete{Children}(array $options = array())
     *
     * @param string $methodName
     *
     * @return mixed
     */
    public function __call($methodName, $args)
    {
        // get{ChildName}, getFirst{ChildName} and get{ChildName}ById
        if (strpos($methodName, 'get') === 0) {
            // getFirst{ChildName}
            if (strpos($methodName, 'getFirst') === 0) {
                $nome_id = mb_substr($methodName, mb_strlen('getFirst'));
                if ($child = $this->_deprecatedFindChild($nome_id)) {
                    return $this->getFirstChild($child['type_id'], (array) $args[0]);
                }
            // get{ChildName}ById
            } elseif (mb_substr($methodName, -4) == 'ById') {
                $nome_id = mb_substr($methodName, mb_strlen('get'), -mb_strlen('ById'));
                if ($child = $this->_deprecatedFindChild($nome_id)) {
                    $options = (array) $args[1];
                    $options['where'][] = 'id = '.intval($args[0]);
                    return $this->getFirstChild($child['type_id'], $options);
                }
            // get{ChildName}ByIdString
            } elseif (mb_substr($methodName, -10) == 'ByIdString') {
                $nome_id = mb_substr($methodName, mb_strlen('get'), -mb_strlen('ByStringId'));
                if ($child = $this->_deprecatedFindChild($nome_id)) {
                    $options = (array) $args[1];
                    $options['where'][] = "id_string = '".$args[0]."'";
                    return $this->getFirstChild($child['type_id'], $options);
                }
            // get{ChildName}Count
            } elseif (mb_substr($methodName, -5) == 'Count') {
                $nome_id = mb_substr($methodName, mb_strlen('get'), -mb_strlen('Count'));
                if ($child = $this->_deprecatedFindChild($nome_id)) {
                    return $this->getChildrenCount($child['type_id'], (array) $args[0]);
                }
            // get{ChildName}
            } else {
                $nome_id = mb_substr($methodName, mb_strlen('get'));
                if ($child = $this->_deprecatedFindChild($nome_id)) {
                    return $this->getChildren($child['type_id'], (array) $args[0]);
                }
            }
        // create{ChildName}
        } elseif (strpos($methodName, 'create') === 0) {
            $nome_id = mb_substr($methodName, mb_strlen('create'));
            if ($child = $this->_deprecatedFindChild($nome_id)) {
                return $this->createChild($child['type_id'], (array) @$args[0]);
            }
        // delete{ChildName}
        } elseif (strpos($methodName, 'delete') === 0) {
            $nome_id = mb_substr($methodName, mb_strlen('delete'));
            if ($child = $this->_deprecatedFindChild($nome_id)) {
                return $this->deleteChildren($child['type_id'], (array) $args[0]);
            }
        }
        return parent::__call($methodName, $args);
    }

    protected function _loadRelationship($relationships, $name)
    {
        $result = parent::_loadRelationship($relationships, $name);
        if ($result && $result instanceof Collection) {
            return $result->all();
        }
        return $result;
    }

    protected function _aliasToColumn($alias, $aliases)
    {
        if (isset($aliases[$alias])) {
            return $aliases[$alias];
        }
        if (isset($aliases[$alias.'_id'])) {
            if (getenv('APP_DEBUG') && getenv('APP_ENV') === 'local') {
                trigger_error($alias.' is a relation, use '.$alias.'_id', E_USER_DEPRECATED);
            }
            return $aliases[$alias.'_id'];
        }
        if (isset($aliases[$alias.'_ids'])) {
            if (getenv('APP_DEBUG') && getenv('APP_ENV') === 'local') {
                trigger_error($alias.' is a relation, use '.$alias.'_ids', E_USER_DEPRECATED);
            }
            return $aliases[$alias.'_ids'];
        }
        return $alias;
    }

    /**
     * Finds a Child Tipo by a camelcase keyword.
     *
     * @param string $nome_id CamelCase
     *
     * @return array
     */
    protected function _deprecatedFindChild($nome_id)
    {
        $children = $this->getTipo()->getInterAdminsChildren();
        if (empty($children[$nome_id])) {
            $nome_id = explode('_', Jp7_Inflector::underscore($nome_id));
            $nome_id[0] = Jp7_Inflector::plural($nome_id[0]);
            $nome_id = Jp7_Inflector::camelize(implode('_', $nome_id));
        }
        if (empty($children[$nome_id])) {
            $nome_id = Jp7_Inflector::plural($nome_id);
        }
        return $children[$nome_id];
    }

    /**
     * Creates and returns a child record.
     *
     * @param int   $type_id
     * @param array $attributes Attributes to be merged into the new record.
     *
     * @return
     */
    public function createChild($type_id, array $attributes = [])
    {
        return $this->getChildrenTipo($type_id)->createInterAdmin($attributes);
    }

    /**
     * Sets the InterAdminTipo object for this record, changing the $_tipo property.
     *
     * @param InterAdminTipo $type
     */
    public function setTipo(InterAdminTipo $type = null)
    {
        return $this->setType($type);
    }

    /**
     * Gets the InterAdminTipo object for this record, which is then cached on the $_tipo property.
     *
     * @param array $options Default array of options. Available keys: class.
     *
     * @return InterAdminTipo
     */
    public function getTipo($options = [])
    {
        return $this->getType($options);
    }

    /**
     * Gets fields values by their alias.
     *
     * @param array|string $fields
     *
     * @see InterAdmin::getFieldsValues()
     *
     * @return
     */
    public function getByAlias($fields)
    {
        if (func_num_args() > 1) {
            throw new Exception('Only 1 argument is expected and it should be an array.');
        }
        if (is_string($fields)) {
            return $this->$fields;
        }
    }
    /**
     * Returns the number of children using COUNT(id).
     *
     * @param int   $type_id
     * @param array $options Default array of options. Available keys: where.
     *
     * @return int Count of InterAdmins found.
     */
    public function getChildrenCount($type_id, $options = [])
    {
        $options['fields'] = ['COUNT(DISTINCT id)'];
        $retorno = $this->getFirstChild($type_id, $options);
        return intval($retorno->count_distinct_id);
    }
    /**
     * Returns the first Child.
     *
     * @param int   $type_id
     * @param array $options [optional]
     *
     * @return InterAdmin
     */
    public function getFirstChild($type_id, $options = [])
    {
        $retorno = $this->getChildren($type_id, ['limit' => 1] + $options);
        return $retorno[0] ?? null;
    }
    /**
     * Returns the first Child by ID.
     *
     * @param int   $type_id
     * @param int   $id
     * @param array $options [optional]
     *
     * @return InterAdmin
     */
    public function getChildById($type_id, $id, $options = [])
    {
        $options['limit'] = 1;
        $options['where'][] = 'id = '.intval($id);
        $retorno = $this->getChildren($type_id, $options);
        return $retorno[0];
    }
    /**
     * Deletes all the children of a given $type_id.
     *
     * @param int   $type_id
     * @param array $options [optional]
     *
     * @return int Number of deleted children.
     */
    public function deleteChildren($type_id, $options = [])
    {
        $children = $this->getChildren($type_id, $options);
        foreach ($children as $child) {
            $child->delete();
        }
        return count($children);
    }
    /**
     *  Deletes the children of a given $type_id forever.
     *
     * @param int   $type_id
     * @param array $options [optional]
     *
     * @return int Count of deleted InterAdmins.
     */
    public function deleteChildrenForever($type_id, $options = [])
    {
        if ($type_id) {
            $type = $this->getChildrenTipo($type_id);
            return $type->deleteInterAdminsForever($options);
        }
    }
    /**
     * Creates a new InterAdminArquivo with type_id, id and mostrar set.
     *
     * @param array $attributes [optional]
     *
     * @return InterAdminArquivo
     */
    public function createArquivo(array $attributes = [])
    {
        $className = static::DEFAULT_NAMESPACE.'InterAdminArquivo';
        if (!class_exists($className)) {
            $className = 'InterAdminArquivo';
        }
        $file = new $className();
        $file->setParent($this);
        $file->setTipo($this->getTipo());
        $file->mostrar = 'S';
        $file->setAttributes($attributes);
        return $file;
    }
    /**
     * Retrieves the uploaded files of this record.
     *
     * @param array $options Default array of options. Available keys: fields, where, order, limit.
     *
     * @return array Array of InterAdminArquivo objects.
     */
    public function getArquivos($options = [])
    {
        return $this->deprecated_getArquivos($options)->all();
    }

    public function getFirstArquivo($options = [])
    {
        $retorno = $this->getArquivos($options + ['limit' => 1]);
        return $retorno[0] ?? null;
    }
    /**
     * Deletes all the InterAdminArquivo records related with this record.
     *
     * @param array $options [optional]
     *
     * @return int Number of deleted arquivos.
     */
    public function deleteArquivos($options = [])
    {
        $files = $this->getArquivos($options);
        foreach ($files as $file) {
            $file->delete();
        }
        return count($files);
    }
    /**
     * Retrieves this record´s children for the given $type_id.
     *
     * @param int   $type_id
     * @param array $options Default array of options. Available keys: fields, where, order, group, limit, class.
     *
     * @return array Array of InterAdmin objects.
     */
    public function getChildren($type_id, $options = [])
    {
        $children = [];
        if ($type_id) {
            $options = $options + ['fields_alias' => static::DEFAULT_FIELDS_ALIAS];
            if ($childrenTipo = $this->getChildrenTipo($type_id)) {
                $children = $childrenTipo->find($options);
            }
        }
        return $children;
    }

    public function getFieldsValues($fields, $forceAsString = false, $fieldsAlias = false)
    {
        if ($forceAsString) {
            throw new Exception('Not implemented');
        }
        if (is_array($fields)) {
            $retorno = (object) [];
            // returns only the fields requested on $fields
            foreach ($fields as $key => $value) {
                if (is_array($value)) {
                    $retorno->$key = $this->$key;
                } else {
                    $retorno->$value = $this->$value;
                }
            }
            return $retorno;
        }
        return $this->$fields;
    }

    /**
     * Updates all the attributes from the passed-in array and saves the record.
     *
     * @param array $attributes Array with fields names and values.
     */
    public function updateAttributes($attributes)
    {
        $this->setRawAttributes($attributes);
        $this->_update($attributes);
    }

    public function save()
    {
        // Using id_string is deprecated, use id_slug instead
        if (array_key_exists('varchar_key', $this->attributes) && in_array('id_string', $this->getColumns())) {
            $this->id_string = toId($this->varchar_key);
        }
        return parent::save();
    }

    protected function _convertForDatabase($attributes, $aliases)
    {
        $valuesToSave = parent::_convertForDatabase($attributes, $aliases);
        foreach ($this->getTipo()->getRelationships() as $name => $data) {
            if (isset($valuesToSave[$name])) {
                if ($data['multi']) {
                    $alias = $aliases[$name.'_ids'];
                    if (getenv('APP_DEBUG') && getenv('APP_ENV') === 'local') {
                        trigger_error($name.' is a relation, use '.$name.'_ids', E_USER_DEPRECATED);
                    }
                } else {
                    $alias = $aliases[$name.'_id'];
                    if (getenv('APP_DEBUG') && getenv('APP_ENV') === 'local') {
                        trigger_error($name.' is a relation, use '.$name.'_id', E_USER_DEPRECATED);
                    }
                }
                if ($alias) {
                    $valuesToSave[$alias] = $valuesToSave[$name];
                    unset($valuesToSave[$name]);
                }
            }
        }
        return $valuesToSave;
    }

    /**
     * Reloads all the attributes.
     *
     * @todo Not implemented yet. Won't work with recursive objects and alias.
     */
    public function reload($fields = null)
    {
        if (is_null($fields)) {
            $fields = array_keys($this->attributes);
            $existingFields = array_merge($this->getAttributesAliases(), $this->getAttributesNames(), $this->getAdminAttributes());
            $fields = array_intersect($fields, $existingFields);
        }
        // Esvaziando valores para forçar atualização
        foreach ($fields as $key) {
            unset($this->attributes[$key]);
        }
        $isAliased = static::DEFAULT_FIELDS_ALIAS;
        $this->getFieldsValues($fields, false, $isAliased);
    }

    /**
     * Creates a object of the given Class name with the same attributes.
     *
     * @param object $className
     *
     * @return InterAdminAbstract An instance of the given Class name.
     */
    public function becomes($className)
    {
        $newobject = new $className();
        $newobject->attributes = $this->attributes;
        return $newobject;
    }

    /**
     * Returns the tags.
     *
     * @param array $options Available keys: where, group, limit.
     *
     * @return array
     */
    public function getTags($options = [])
    {
        if (!$this->_tags || $options) {
            $db = $this->getDb();
            $options['where'][] = 'parent_id = '.$this->id;
            $sql = 'SELECT * FROM '.$db->getTablePrefix().'tags '.
                //'WHERE '.implode(' AND ', $options['where']).
                (($options['group']) ? ' GROUP BY '.$options['group'] : '').
                (($options['limit']) ? ' LIMIT '.$options['limit'] : '');
            $rs = $db->select($sql);
            $this->_tags = [];
            foreach ($rs as $row) {
                if ($tag_tipo = InterAdminTipo::getInstance($row->type_id)) {
                    $tag_text = $tag_tipo->nome;
                    if ($row->id) {
                        $options = [
                            'fields' => ['varchar_key'],
                            'where' => ['id = '.$row->id],
                        ];
                        if ($tag_registro = $tag_tipo->findFirst($options)) {
                            $tag_text = $tag_registro->varchar_key.' ('.$tag_tipo->nome.')';
                            $tag_registro->interadmin = $this;
                            $retorno[] = $tag_registro;
                        }
                    } else {
                        $tag_tipo->interadmin = $this;
                        $retorno[] = $tag_tipo;
                    }
                }
            }
        } else {
            $retorno = $this->_tags;
        }
        if (!$options) {
            $this->_tags = $retorno; // cache somente para getTags sem $options
        }
        return (array) $retorno;
    }

    /**
     * Sets the tags for this record. It DELETES the previous records.
     *
     * @param array $tags Array of object to be saved as tags.
     */
    public function setTags(array $tags)
    {
        $db = $this->getDb();
        $sql = 'DELETE FROM '.$db->getTablePrefix().'tags WHERE parent_id = '.$this->id;
        foreach ($tags as $tag) {
            $sql = 'INSERT INTO '.$db->getTablePrefix().'tags (parent_id, id, type_id) VALUES
                ('.$this->id.','.
                ($tag instanceof self ? $tag->id : 0).','.
                ($tag instanceof self ? $tag->type_id : $tag->type_id).')';
            if (!$db->insert($sql)) {
                throw new Jp7_Interadmin_Exception($db->ErrorMsg());
            }
        }
    }

    public function getTagFilters()
    {
        return '(tags.id = '.$this->id." AND tags.type_id = '".$this->getTipo()->type_id."')";
    }

    public function setFieldBySearch($attribute, $searchValue, $searchColumn = 'varchar_key')
    {
        return $this->setAttributeBySearch($attribute, $searchValue, $searchColumn);
    }
}
