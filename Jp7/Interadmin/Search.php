<?php

class Jp7_Interadmin_Search
{
    private $booleanMode = false;

    public function search($search, $date_filter = false, $exclude_tables = [], $extra_where = '')
    {
        global $db;

        $sql = $this->getSql($search, $date_filter, $exclude_tables, $extra_where);
        $rs = $db->Execute($sql);
        if ($rs === false) {
            throw new Jp7_Interadmin_Exception($db->ErrorMsg());
        }
        //krumo($sql);

        $rows = [];
        while ($row = $rs->FetchNextObj()) {
            $rows[] = $row;
        }

        $rs->Close();

        return $rows;
    }

    public function checkIndexes($exclude_tables = [])
    {
        global $db;
        $executedSqls = [];
        $tables = array_diff($this->getTables(), $exclude_tables && is_array($exclude_tables) ? $exclude_tables : []);
        foreach ($tables as $table) {
            $indexes = $db->MetaIndexes($table);
            $columns = $db->MetaColumnNames($table);
            if ($columns) {
                $textColumns = array_filter($columns, [$this, 'isText']);
                if ($textColumns) {
                    if (count($textColumns) > 16) {
                        $textColumns = array_slice($textColumns, 0, 16);
                    }
                    $index = $indexes['interadmin_search'] ?? '';
                    if (!$index || array_full_diff($index['columns'], $textColumns)) {
                        $sql = $this->getIndexSql($table, $textColumns, $index);
                        $executedSqls[] = $sql;
                        $db->Execute($sql);
                    }
                }
            }
        }
        return $executedSqls;
    }

    //ALTER TABLE `teste`.`interadmin_teste` DROP INDEX `search` ,
    //ADD FULLTEXT `search` (
    //`varchar_key` ,
    //`text_1` ,
    //`text_2`
    //)
    public function getIndexSql($table, $columns, $drop = false)
    {
        $sql = 'ALTER TABLE ' . $table . ' ' .
            ($drop ? 'DROP INDEX `interadmin_search` ,' : '') .
            'ADD FULLTEXT `interadmin_search` (' . implode(',', $columns) . ')';

        return $sql;
    }

    public function getSql($search, $date_filter, $exclude_tables = [], $extra_where = '')
    {
        $tables = array_diff($this->getTables(), $exclude_tables);
        $sqls = [];
        foreach ($tables as $table) {
            $tableSql = $this->getTableSql($table, $search, $date_filter, $extra_where);
            if ($tableSql) {
                $sqls[] = $tableSql;
            }
        }

        return '(' . implode("\n) UNION ALL (\n", $sqls) . ') ORDER BY relevance DESC LIMIT 10000';
    }

    public function getTables()
    {
        global $db_prefix;

        $options = [
            'fields' => 'table',
            'group' => 'table',
            'where' => $this->getTipoFilter(),
            'class' => 'InterAdminTipo',
        ];

        $tables = [];
        $types = InterAdminTipo::findTipos($options);
        foreach ($types as $type) {
            $tables[] = $db_prefix . '_' . ($type->table ?: 'records');
        }
        $tables[] = $db_prefix . '_types';
        return $tables;
    }

    /**
     * SQL de uma tabela apenas.
     *
     * @param string $table
     * @param string $search
     * @param bool   $date_filter
     *
     * @return string
     */
    public function getTableSql($table, $search, $date_filter, $extra_where = '')
    {
        global $db, $s_session;

        $columns = $db->MetaColumnNames($table);
        if (!$columns) {
            return false;
        }
        $textColumns = array_filter($columns, [$this, 'isText']);
        if (!$textColumns) {
            return false;
        }
        if (count($textColumns) > 16) {
            $textColumns = array_slice($textColumns, 0, 16);
        }
        $fields = [];
        $fields[] = in_array('id', $columns) ? 'id' : '0 AS id';
        $fields[] = in_array('type_id', $columns) ? 'type_id' : '0 AS type_id';
        $fields[] = in_array('varchar_key', $columns) ? 'varchar_key' : reset($textColumns) . ' AS varchar_key';
        if (in_array('text_1', $columns)) {
            $fields[] = 'text_1';
        } elseif (in_array('texto', $columns)) {
            $fields[] = 'texto AS text_1';
        } else {
            $fields[] = "'' AS text_1";
        }
        //$fields[] = "'$table' AS tablename";

        $short_words = ['de', 'do', 'da', 'ao', 'em', 'no', 'na'];

        $where = [];
        $matches = [];
        // Trata as aspas como uma palavra só
        preg_match_all('/-?"(?:\\\\.|[^\\\\"])*"|[^" ]+/', $search, $matches);
        $words = $matches[0];

        foreach ($words as $key => $word) {
            $isNegative = strpos($word, '-') === 0;
            $words[$key] = $word = ltrim($word, '-');

            $isQuoted = strpos($word, '"') === 0;
            $words[$key] = $word = trim($word, '"');

            if (!$word) {
                continue;
            }

            if ($isNegative) {
                $where[] = 'CONCAT(' . implode(',', $textColumns) . ") NOT LIKE '%" . $word . "%'";
                if ($isQuoted) {
                    $word = '"' . $word . '"';
                }
                $search = preg_replace('/(^|[ ])-' . preg_quote($word) . '([ ]|$)/', '\1\2', $search);
                unset($words[$key]);
                continue;
            } elseif ($isQuoted) {
                $where[] = 'CONCAT(' . implode(',', $textColumns) . ") LIKE '%" . $word . "%'";
            }
            if (mb_strlen($word) < 2 || in_array($word, $short_words)) {
                unset($words[$key]);
            }
        }
        $search = trim($search);
        $match = 'MATCH (' . implode(',', $textColumns) . ") AGAINST ('" . addslashes($search) . "'" . ($this->booleanMode ? ' IN BOOLEAN MODE' : '') . ')';

        if ($words) {
            $words = array_unique($words);
            $weight = round(5 / count($words), 1);
            foreach ($words as $word) {
                $plural = [];
                $plural[] = $word;
                if (!str_ends_with($word, '*')) {
                    $plural[] = Jp7_Inflector::plural($word);
                }
                $plural = array_unique($plural);
                if (mb_strlen($word) > 4) {
                    $like = [];
                    foreach ($plural as $word) {
                        $like[] = reset($textColumns) . " LIKE '%" . str_replace('*', '%', addslashes($word)) . "%'";
                    }
                    $match .= ' + (' . implode(' OR ', $like) . ') * ' . $weight;
                } else {
                    $regex = implode('|', $plural);
                    $regex = addcslashes($regex, '[]()+?.');
                    $regex = $this->regexDiacritics($regex);
                    $regex = str_replace('*', '[[:alnum:]]*', $regex);
                    //$match .= " + (" . reset($textColumns) . " REGEXP '(^|[^a-zA-Z])(" . $regex . ")([^a-zA-Z]|$)') * " . $weight;
                    // [[:<:]] é igual \b - Início e fim de uma palavra
                    $match .= ' + (' . reset($textColumns) . " REGEXP '[[:<:]]" . $regex . "[[:>:]]') * " . $weight;
                }
            }
            //$match .= " + (CONCAT(" . implode(',', $textColumns) . ") LIKE '%" . addslashes($search) . "%') * 5";
        }

        $where[] = $this->getTipoFilter();
        if ($extra_where) {
            $where[] = $extra_where;
        }
        $where[] = 'type_id > 0';

        if ($date_filter) {
            if (in_array('publish_at', $columns)) {
                $date_filters = [
                    'day' => 'publish_at >= DATE_SUB(CURDATE(), INTERVAL 1 DAY)',
                    'week' => 'publish_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)',
                    'month' => 'publish_at >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)',
                    'year' => 'publish_at >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)',
                ];

                if ($date_filters[$date_filter]) {
                    $where[] = $date_filters[$date_filter];
                }
            } else {
                $where[] = '0 = 1';
            }
        }

        if (!$s_session['deleted']) {
            $deleted_column = in_array('deleted_at', $columns) ? 'deleted_at' : '';
            if (!$deleted_column && in_array('deleted_at', $columns)) {
                $deleted_column = 'deleted_at';
            }
            if ($deleted_column) {
                $where[] = $deleted_column . " = ''";
            }
        }
        //"CONCAT(" . implode(',', $textColumns) . ") LIKE '%" . $word . "%'"
        if ($s_session['filter_publish']) {
            if (in_array('char_key', $columns)) {
                $where[] = "char_key <> ''";
            }
            if (in_array('mostrar', $columns)) {
                $where[] = "mostrar <> ''";
            }
            if (in_array('publish', $columns)) {
                $where[] = "publish <> ''";
            }
            if (in_array('expire_at', $columns)) {
                $where[] = "(expire_at > '" . date('c') . "' OR expire_at = '0000-00-00 00:00:00')";
            }
        }

        $hits_field = in_array('hits', $columns) ? ' * IF (hits > 1000, 1.3, 1) ' : '';

        //$hits_field = in_array('type_id', $columns) ? ' + IF (type_id IN (1, 23), 5, 1) ' : '';

        $sql = 'SELECT ' . implode(',', $fields) . ', ' . $match . $hits_field . ' AS relevance ' .
            'FROM `' . $table . '` ' .
            'WHERE ' . implode(' AND ', $where) . ' ' .
            'HAVING relevance > 0';

        return $sql;
    }
    /**
     * SQL de permissões dos tipos.
     *
     * @return string
     */
    public function getTipoFilter()
    {
        global $s_allowed_tipos;
        if (is_array($s_allowed_tipos)) {
            if ($s_allowed_tipos) {
                return 'type_id IN (' . implode(',', $s_allowed_tipos) . ')';
            } else {
                return '0=1';
            }
        } else {
            return '1=1';
        }
    }

    public function isText($column)
    {
        if (strpos($column, 'varchar_') === 0 || strpos($column, 'text_') === 0) {
            $parts = explode('_', $column);
            $sufixo = end($parts);
            if (!is_numeric($sufixo)) {
                return true;
            } elseif ($sufixo < 4) {
                return true;
            }
        } elseif (strpos($column, 'text') === 0 || strpos($column, 'nome') === 0) {
            return true;
        }

        return false;
    }

    public function regexDiacritics($word)
    {
        $word = str_replace('a', '[áàãâäªa]', $word);
        $word = str_replace('e', '[éèêë&e]', $word);
        $word = str_replace('i', '[íìîïi]', $word);
        $word = str_replace('o', '[óòõôöºo]', $word);
        $word = str_replace('u', '[úùûüu]', $word);
        $word = str_replace('c', '[çc]', $word);
        $word = str_replace('n', '[ñn]', $word);

        return $word;
    }
}
