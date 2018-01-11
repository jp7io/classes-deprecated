<?php

/**
 * Class for handling collections of objects.
 */
class Jp7_Collections
{
    /**
     * Acts like a SELECT statement. Performs 'where', 'order', 'group' and 'limit'.
     *
     * @param array $array
     * @param array $options Available keys are 'where', 'order', 'group' and 'limit'.
     *
     * @return array Processed collection.
     */
    public static function query($array, $options)
    {
        if ($array) {
            if ($options['where']) {
                $array = self::filter($array, $options['where']);
            }
            if ($options['group']) {
                $array = self::group($array, $options['group']);
            }
            if ($options['order']) {
                $array = self::sort($array, $options['order']);
            }
            if ($options['limit']) {
                $array = self::slice($array, $options['limit']);
            }
        }

        return $array;
    }
    /**
     * Acts like a SQL GROUP BY  statement.
     *
     * @param array  $array
     * @param string $clause
     *
     * @return array
     */
    public static function group($array, $clause)
    {
        $clause = preg_replace('/\s+/', ' ', $clause);
        $keys = explode(',', $clause);

        $novaArray = [];
        $hashExistente = [];

        foreach ($array as $item) {
            $hash = ':';
            foreach ($keys as $key) {
                $hash .= mb_strtolower(jp7_normalize($item->$key));
            }
            if (!$hashExistente[$hash]) {
                $novaArray[] = $item;
                $hashExistente[$hash]++;
            }
        }

        return $novaArray;
    }
    /**
     * Filters the array using SQL Where.
     *
     * @param array  $array
     * @param string $clause Similar to SQL WHERE Clause, only supports simple comparations for now.
     *
     * @return array
     */
    public static function filter($array, $clause, $debug = false)
    {
        return array_filter($array, self::_clauseToFunction($clause, $debug));
    }

    public static function detect($array, $clause, $debug = false)
    {
        $function = self::_clauseToFunction($clause);
        foreach ($array as $item) {
            if ($function($item)) {
                return $item;
            }
        }
    }

    private static function _clauseToFunction($clause, $debug = false)
    {
        if (is_array($clause)) {
            $clause = implode(' AND ', $clause);
        }
        $clause = preg_replace('/([^=!])(=)([^=])/', '\1\2=\3', $clause);
        $clause = preg_replace('/(?<!\')(\b[a-zA-Z_][a-zA-Z0-9_.]+\b)/', '$a->\1', $clause);
        // FIXME Fazer um parser melhor depois
        $clause = str_replace('.', '->', $clause);
        $clause = str_replace(' $a->OR ', ' OR ', $clause);
        $fnBody = 'return '.$clause.';';
        if ($debug) {
            dump($fnBody);
        }

        return eval('return function ($a) { '.$fnBody.'};');
    }

    /**
     * Flips an array of $itens->subitem into an array of $subitem->itens;.
     *
     * @param object $compactedArray Such as array('newPropertyName' => $array).
     * @param object $property       Name of the property to be flipped.
     *
     * @return array
     */
    public static function flip($compactedArray, $property)
    {
        $newPropertyName = key($compactedArray);
        $subitens = [];
        foreach ($compactedArray[$newPropertyName] as $item) {
            $subitem = $item->$property;
            unset($item->$property);
            if (is_object($subitem)) {
                $key = $subitem->__toString();
                if (!array_key_exists($key, $subitens)) {
                    $subitem->$newPropertyName = [];
                    $subitens[$key] = $subitem;
                }
                $subitens[$key]->{$newPropertyName}[] = $item;
            }
        }
        // Returning values with reindexed keys
        return array_values($subitens);
    }

    public static function separate($array, $property)
    {
        $separated = [];
        foreach ($array as $item) {
            $separated[$item->$property][] = $item;
        }

        return $separated;
    }

    /**
     * Acts like an order by on an SQL.
     *
     * @param array  $array  The array we want to sort.
     * @param string $clause A string specifying how to sort the array similar to SQL ORDER BY clause.
     *
     * @return array
     */
    public static function sort($array, $clause, $debug = false)
    {
        $dirMap = ['desc' => 1, 'asc' => -1];

        $clause = preg_replace('/\s+/', ' ', $clause);
        $keys = explode(',', $clause);

        $retorno = 'return 0;';

        for ($i = count($keys) - 1; $i >= 0; $i--) {
            $parts = explode(' ', trim($keys[$i]));
            $dir = array_pop($parts);

            if (mb_strtolower($dir) == 'asc' || mb_strtolower($dir) == 'desc') {
                $t = $dirMap[mb_strtolower($dir)];
            } else {
                $parts[] = $dir;
                $t = $dirMap['asc'];
            }
            $k = implode(' ', $parts);
            $f = -1 * $t;

            if ($k == 'RAND()') {
                $aStr = $bStr = 'rand()';
            } else {
                $k = str_replace('.', '->', $k);
                $aStr = '$a->'.$k;
                $bStr = '$b->'.$k;
            }

            // Checagem de string para usar collate correto
            if (strpos($k, '=') !== false) {
                $valor = null;
                $aStr = '('.preg_replace('/([^=!])(=)([^=])/', '\1\2=\3', $aStr).')';
                $bStr = '('.preg_replace('/([^=!])(=)([^=])/', '\1\2=\3', $bStr).')';
            } else {
                $attr = explode('->', $k);
                $valor = reset($array)->{$attr[0]};
                if (!empty($attr[2])) {
                    $valor = $valor->{$attr[1]}->{$attr[2]};
                } elseif (!empty($attr[1])) {
                    $valor = $valor->{$attr[1]};
                }
            }
            if (is_string($valor) && !is_numeric($valor)) {
                $fnBody = '$cmp = strcoll('.$aStr.', '.$bStr.');'.
                    ' if ($cmp == 0) {'.
                        $retorno.
                    ' } '.
                    'return ($cmp < 0) ? '.$t.' : '.$f.';';
            } else {
                $fnBody = 'if ('.$aStr.' == '.$bStr.') {'.
                        $retorno.
                    ' } '.
                    'return ('.$aStr.' < '.$bStr.') ? '.$t.' : '.$f.';';
            }
            $retorno = &$fnBody;
        }
        if ($debug) {
            dump($fnBody);
        }
        if ($fnBody) {
            usort($array, eval('return function ($a,$b) { '.$fnBody.'};'));
        }

        return $array;
    }
    /**
     * Acts like a LIMIT statement on SQL.
     *
     * @param array  &$array
     * @param string $clause Similar to SQL LIMIT clause.
     *
     * @return array
     */
    public static function slice($array, $clause)
    {
        if (strpos($clause, ',')) {
            $l = explode(',', $clause);
            $offset = trim($l[0]);
            $length = trim($l[1]);
        } else {
            $offset = 0;
            $length = trim($clause);
        }

        return array_slice($array, $offset, $length);
    }
    /**
     * Implodes the properties of an array of objects.
     * It uses getFieldsValues() for InterAdminTipo and getByAlias() for InterAdmin.
     *
     * @param string $separator
     * @param array  $array              Array of objects.
     * @param string $propertyName       [optional] Defaults to 'nome'.
     * @param bool   $discardEmptyValues If TRUE empty values won´t be imploded.
     *
     * @return string Values of $propertyName imploded using $separator.
     */
    public static function implode($separator, $array, $propertyName = 'nome', $discardEmptyValues = true)
    {
        $stringArr = [];
        foreach ($array as $item) {
            $stringArr[] = $item->$propertyName;
        }
        if ($discardEmptyValues) {
            return jp7_implode($separator, $stringArr);
        } else {
            return implode($separator, $stringArr);
        }
    }

    /**
     * Transforms prefixed keys into arrays. Ex:
     * $_POST['foto_id'][] = 1;
     * $_POST['foto_name'][] = 'Teste';.
     *
     * $foto = prefixToArray($_POST, 'foto');
     *
     * Returns:
     * $foto[0] = array('id' => 1, 'name' => 'Teste');
     *
     * @param array  $multiarray Such as $_POST.
     * @param string $prefix     Such as 'foto' for 'foto_'
     *
     * @return array
     */
    public static function prefixToArray($multiarray, $prefix)
    {
        $newarray = [];
        $preflen = mb_strlen($prefix) + 1;
        foreach ($multiarray as $name => $array) {
            $namekey = mb_substr($name, $preflen);
            if (strpos($name, $prefix.'_') === 0) {
                foreach ($array as $key => $value) {
                    $newarray[$key][$namekey] = $value;
                }
            }
        }

        return $newarray;
    }

    public static function getFieldsValues($array, $fields, $fields_alias)
    {
        if (count($array) > 0) {
            $first = reset($array);

            $tipo = $first->getTipo();
            $retornos = $tipo->find([
                'class' => 'InterAdmin',
                'fields' => $fields,
                'fields_alias' => $fields_alias,
                'where' => ['id IN ('.implode(',', $array).')'],
                'order' => 'FIELD(id,'.implode(',', $array).')',
                //'debug' => true
            ]);
            foreach ($retornos as $key => $retorno) {
                $array[$key]->attributes = $retorno->attributes + $array[$key]->attributes;
            }
        }
    }
}
