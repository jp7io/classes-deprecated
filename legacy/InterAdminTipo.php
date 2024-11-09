<?php

use Jp7\Interadmin\Type;
use Jp7\Interadmin\Record;

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
 * Class which represents records on the table interadmin_{client name}_tipos.
 *
 * @deprecated use Type instead
 */
class InterAdminTipo extends Type implements InterAdminAbstract
{
    const DEFAULT_NAMESPACE = '';

    /**
     * Magic method calls(On Development).
     *
     * Available magic methods:
     * - findBy{Field}(mixed $value, array $options = array())
     * - findFirstBy{Field}(mixed $value, array $options = array())
     *
     * @param string $method
     *
     * @return mixed
     */
    public function __call($method, $args)
    {
        if (strpos($method, 'find') === 0) {
            if (preg_match('/find(First)?By(?<args>.*)/', $method, $match)) {
                $termos = explode('And', $match['args']);
                $options = $args[count($termos)];
                foreach ($termos as $key => $termo) {
                    $options['where'][] = Jp7_Inflector::underscore($termo) . " = '" . addslashes($args[$key]) . "'";
                }
                if ($match[1]) {
                    $options['limit'] = 1;
                }
                $retorno = $this->find($options);
                return ($match[1]) ? reset($retorno) : $retorno;
            }
        }
        return parent::__call($method, $args);
    }

    /**
     * Retrieves the unique record which have this id.
     *
     * @param int   $id      Search value.
     * @param array $options
     *
     * @return InterAdmin First InterAdmin object found.
     */
    public function findById($id, $options = [])
    {
        $options['where'][] = 'id = ' . intval((string) $id);
        return $this->deprecatedFindFirst($options);
    }

    /**
     * @param array $options Default array of options. Available keys: fields, where, order, group, class.
     *
     * @return InterAdmin First InterAdmin object found.
     */
    public function findFirst($options = [])
    {
        $result = $this->find(['limit' => 1] + $options);
        return reset($result);
    }

    /**
     * Retrieves the first record which have this id_string.
     *
     * @param string $id_string Search value.
     *
     * @return InterAdmin First InterAdmin object found.
     */
    public function findByIdString($id_string, $options = [])
    {
        $options['where'][] = "id_string = '" . $id_string . "'";
        return $this->findFirst($options);
    }

    /**
     * @param array $options Default array of options. Available keys: fields, where, order, group, limit, class.
     *
     * @return array InterAdmin[] Array of InterAdmin objects.
     */
    public function find($options = [])
    {
        return $this->deprecatedFind($options)->all();
    }

    public function distinct($column, $options = [])
    {
        return parent::deprecated_distinct($column, $options);
    }

    public function max($column, $options = [])
    {
        return parent::deprecated_max($column, $options);
    }

    public function min($column, $options = [])
    {
        return parent::deprecated_min($column, $options);
    }

    public function sum($column, $options = [])
    {
        return parent::deprecated_sum($column, $options);
    }

    public function avg($column, $options = [])
    {
        return parent::deprecated_avg($column, $options);
    }

    public function aggregate($function, $column, $options)
    {
        return parent::deprecated_aggregate($function, $column, $options);
    }

    /**
     * Returns the number of InterAdmins using COUNT(id).
     *
     * @param array $options Default array of options. Available keys: where.
     *
     * @return int Count of InterAdmins found.
     */
    public function count($options = [])
    {
        if ($options['group'] == 'id') {
            // O COUNT() precisa trazer a contagem total em 1 linha
            // Caso exista GROUP BY id, ele traria em várias linhas
            // Esse é um tratamento especial apenas para o ID
            $options['fields'] = ['COUNT(DISTINCT id) AS count_id'];
            unset($options['group']);
        } elseif ($options['group']) {
            // Se houver GROUP BY com outro campo, retornará a contagem errada
            throw new Exception('GROUP BY is not supported when using count().');
        } else {
            $options['fields'] = ['COUNT(id) AS count_id'];
        }
        $retorno = $this->deprecatedFindFirst($options);
        return intval($retorno->count_id);
    }

    /**
     * Retrieves the children of this InterAdminTipo.
     *
     * @param array $options Default array of options. Available keys: fields, where, order, class.
     *
     * @return array Array of InterAdminTipo objects.
     */
    public function getChildren($options = [])
    {
        $this->_whereArrayFix($options['where']); // FIXME
        $options['where'][] = 'parent_type_id = ' . $this->type_id;
        return $this->deprecatedGetChildren($options)->all();
    }

    public function getNome()
    {
        return $this->getName();
    }

    /**
     * Returns all records having an InterAdminTipo that uses this as a model (model_type_id).
     *
     * @param array $options [optional]
     *
     * @return array InterAdmin[]
     */
    public function getInterAdminsUsingThisModel($options = [])
    {
        $this->_prepareInterAdminsOptions($options, $optionsInstance);
        $types = $this->getTiposUsingThisModel();
        $options['where'][] = 'type_id IN (' . implode(',', $types) . ')';
        $rs = $this->_executeQuery($options);
        $records = [];
        foreach ($rs as $row) {
            $record = InterAdmin::getInstance($row->id, $optionsInstance, $types[$row->type_id]);
            $this->_getAttributesFromRow($row, $record, $options);
            $records[] = $record;
        }
        return $records;
    }

    protected function _aliasToColumn($alias, $aliases)
    {
        if (isset($aliases[$alias])) {
            return $aliases[$alias];
        }
        if (isset($aliases[$alias . '_id'])) {
            return $aliases[$alias . '_id'];
        }
        if (isset($aliases[$alias . '_ids'])) {
            return $aliases[$alias . '_ids'];
        }
        return $alias;
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
     * Gets the first child.
     *
     * @param array $options [optional]
     *
     * @return InterAdminTipo
     */
    public function getFirstChild($options = [])
    {
        $retorno = $this->getChildren(['limit' => 1] + $options);
        return $retorno[0];
    }

    /**
     * Retrieves the first child of this InterAdminTipo with the given "model_type_id".
     *
     * @param string|int $model_type_id
     * @param array      $options       Default array of options. Available keys: fields, where, order, class.
     *
     * @return InterAdminTipo
     */
    public function getFirstChildByModel($model_type_id, $options = [])
    {
        $retorno = $this->getChildrenByModel($model_type_id, ['limit' => 1] + $options);
        return $retorno[0];
    }
    /**
     * Retrieves the first child of this InterAdminTipo with the given "nome".
     *
     * @param array $options Default array of options. Available keys: fields, where, order, class.
     *
     * @return InterAdminTipo
     */
    public function getFirstChildByNome($nome, $options = [])
    {
        $options['where'][] = "nome = '" . $nome . "'";
        return $this->getFirstChild($options);
    }
    /**
     * Retrieves the children of this InterAdminTipo which have the given model_type_id.
     *
     * @param array $options Default array of options. Available keys: fields, where, order, class.
     *
     * @return Array of InterAdminTipo objects.
     */
    public function getChildrenByModel($model_type_id, $options = [])
    {
        $options['where'][] = "model_type_id = '" . $model_type_id . "'";
        // Necessário enquanto algumas tabelas ainda tem esse campo numérico
        $options['where'][] = "model_type_id != '0'";
        return $this->getChildren($options);
    }

    /**
     * Creates a record with type_id, mostrar, created_at and publish_at filled.
     *
     * @param array $attributes Attributes to be merged into the new record.
     *
     * @return InterAdmin
     */
    public function createInterAdmin(array $attributes = [])
    {
        $options = ['default_namespace' => static::DEFAULT_NAMESPACE];
        $record = Record::getInstance(0, $options, $this);
        if ($mostrar = $this->getFieldsAlias('char_key')) {
            $record->$mostrar = 'S';
        }
        $record->publish_at = date('c');
        $record->created_at = date('c');
        $record->publish = 'S';
        $record->log = '';

        if ($this->_parent instanceof Record) {
            $record->setParent($this->_parent);
        }

        $record->setAttributes($attributes);
        return $record;
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

    public function getTagFilters()
    {
        return '(tags.type_id = ' . $this->type_id . ' AND tags.id = 0)';
    }

    public function getBreadcrumb()
    {
        $parents = [];
        $parent = $this;
        do {
            $parents[] = $parent;
        } while (($parent = $parent->getParent()) && $parent->type_id);
        return array_reverse($parents);
    }

    public function deleteInterAdminsForever($options = [])
    {
        $this->deprecated_deleteInterAdminsForever($options);
    }

    public function deleteInterAdmins($options = [])
    {
        $this->deprecated_deleteInterAdmins($options);
    }

    public function updateAttributes($attributes)
    {
        $this->setRawAttributes($attributes);
        $this->_update($attributes);
    }
    /**
     * Retrieves the first Type from the database.
     *
     * @param array $options [optional]
     *
     * @return Type
     */
    public static function findFirstTipo($options = [])
    {
        $types = self::findTipos(['limit' => 1] + $options);

        return empty($types) ? null : $types[0];
    }
    /**
     * Retrieves the first Type with the given "model_type_id".
     *
     * @param string|int $model_type_id
     * @param array      $options       [optional]
     *
     * @return Type
     */
    public static function findFirstTipoByModel($model_type_id, $options = [])
    {
        return self::findTiposByModel($model_type_id, ['limit' => 1] + $options)[0];
    }
    /**
     * Retrieves all the Type with the given "model_type_id".
     *
     * @param string|int $model_type_id
     * @param array      $options       [optional]
     *
     * @return array
     */
    public static function findTiposByModel($model_type_id, $options = [])
    {
        $options['where'][] = "model_type_id = '" . $model_type_id . "'";
        if ($model_type_id != '0') {
            // Devido à mudança de int para string do campo model_type_id, essa linha é necessária
            $options['where'][] = "model_type_id != '0'";
        }

        return self::findTipos($options);
    }
    /**
     * Retrieves multiple Type's from the database.
     *
     * @param array $options [optional]
     *
     * @return Type[]
     */
    public static function findTipos($options = [])
    {
        $instance = new self();
        if (isset($options['db'])) {
            $instance->setDb($options['db']);
        }
        if (!isset($options['fields'])) {
            $options['fields'] = [];
        }
        $options['fields'] = array_merge(['type_id'], (array) $options['fields']);

        $options['from'] = $instance->getTableName() . ' AS main';
        if (empty($options['where'])) {
            $options['where'][] = '1 = 1';
        }
        if (empty($options['order'])) {
            $options['order'] = 'ordem, nome';
        }
        // Internal use
        $options['aliases'] = $instance->getAttributesAliases();
        $options['campos'] = $instance->getAttributesCampos();

        $rs = $instance->_executeQuery($options);
        $types = [];

        foreach ($rs as $row) {
            $type = self::getInstance($row->type_id, [
                'db' => isset($options['db']) ? $options['db'] : null,
                'class' => isset($options['class']) ? $options['class'] : null,
            ]);
            $instance->_getAttributesFromRow($row, $type, $options);
            $types[] = $type;
        }

        return $types;
    }
}
