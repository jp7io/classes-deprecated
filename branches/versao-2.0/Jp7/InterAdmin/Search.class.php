<?php

//ALTER TABLE `teste`.`interadmin_teste` DROP INDEX `search` ,
//ADD FULLTEXT `search` (
//`varchar_key` ,
//`text_1` ,
//`text_2`
//)

class Jp7_InterAdmin_Search {
	private $booleanMode = false;
	
	public function search($search) {
		global $db;
		
		$sql = $this->getSql($search);
		$rs = $db->Execute($sql) or die(jp7_debug($db->ErrorMsg(), $sql));
		//krumo($sql);
		
		$rows = array();
		while ($row = $rs->FetchNextObj()) {
			$rows[] = $row;
		}
		
		$rs->Close();
		return $rows;
	}
	
	public function checkIndexes() {
		global $db;
		$tables = $this->getTables();
		foreach ($tables as $table) {
			$indexes = $db->MetaIndexes($table);
			if ($indexes !== false) { // false = não existe
				$columns = $db->MetaColumnNames($table);
				$textColumns = array_filter($columns, array($this, 'isText'));
				if ($textColumns) {
					$index = $indexes['interadmin_search'];
					if (!$index || array_full_diff($index['columns'], $textColumns)) {
						$sql = $this->getIndexSql($table, $textColumns, $index);
						$db->Execute($sql);
					}
				}
			}
		}
	}
	
	public function getIndexSql($table, $columns, $drop = false) {
		$sql = 'ALTER TABLE ' . $table . ' ' .
			($drop ? 'DROP INDEX `interadmin_search` ,' : '') .
			'ADD FULLTEXT `interadmin_search` (' . implode(',', $columns) . ')';
		return $sql;
	}
	
	public function getSql($search) {
		global $db;
		
		$tables = $this->getTables();
		$sqls = array();
		foreach ($tables as $table) {
			$tableSql = $this->getTableSql($table, $search);
			if ($tableSql) {
				$sqls[] = $tableSql;
			}
		}
		return '(' . implode("\n) UNION ALL (\n", $sqls) . ') ORDER BY relevance DESC LIMIT 10000';
	}
	
	public function getTables() {
		global $db_prefix;
		
		$options = array(
			'fields' => 'tabela',
			'group' => 'tabela',
			'where' => $this->getTipoFilter(),
			'class' => 'InterAdminTipo'
		);
		
		$tables = array();
		$tipos = InterAdminTipo::findTipos($options);
		foreach ($tipos as $tipo) {
			$tables[] = $db_prefix . ($tipo->tabela ? '_' . $tipo->tabela : '');
		}
		$tables[] = $db_prefix . '_tipos';
		return $tables;
	}
	
	/**
	 * SQL de uma tabela apenas
	 * @param string $table
	 * @param string $search
	 * @param bool $count
	 * @return string
	 */ 
	public function getTableSql($table, $search) {
		global $db, $s_session;
		
		$columns = $db->MetaColumnNames($table);
		if (!$columns) {
			return false;
		}		
		$textColumns = array_filter($columns, array($this, 'isText'));
		if (!$textColumns) {
			return false;
		}
		$fields = array();		
		$fields[] = in_array('id', $columns) ? 'id' : '0 AS id';
		$fields[] = in_array('id_tipo', $columns) ? 'id_tipo' : '0 AS id_tipo';
		$fields[] = in_array('varchar_key', $columns) ? 'varchar_key' : reset($textColumns) . " AS varchar_key";
		if (in_array('text_1', $columns)) {
			$fields[] = 'text_1';
		} elseif (in_array('texto', $columns))  {
			$fields[] = 'texto AS text_1';
		} else {
			$fields[] = "'' AS text_1";
		}
		
		$short_words = array('de', 'do', 'da', 'ao', 'em', 'no', 'na');
						
		$where = array();
		// Trata as aspas como uma palavra só
		preg_match_all('/-?"(?:\\\\.|[^\\\\"])*"|[^" ]+/', $search, $matches);
		$words = $matches[0];
		
		foreach ($words as $key => $word) {
			$isNegative = strpos($word, '-') === 0;
			$words[$key] = $word = ltrim($word, '-');
			
			$isQuoted = strpos($word, '"') === 0;
			$words[$key] = $word = trim($word, '"');
			
			if ($isNegative) {
				$where[] = "CONCAT(" . implode(',', $textColumns) . ") NOT LIKE '%" . $word . "%'";
				if ($isQuoted) {
					$word = '"' . $word . '"';
				}
				$search = preg_replace('/(^|[ ])-' . preg_quote($word) . '([ ]|$)/', '\1\2', $search);
				unset($words[$key]);
				continue;	
			} elseif ($isQuoted) {
				$where[] = "CONCAT(" . implode(',', $textColumns) . ") LIKE '%" . $word . "%'";
			}
			if (strlen($word) < 2 || in_array($word, $short_words)) {
				unset($words[$key]);
			}
		}
		$search = trim($search);
		$match = "MATCH (" . implode(',', $textColumns) . ") AGAINST ('" . addslashes($search) . "'" . ($this->booleanMode ? " IN BOOLEAN MODE" : "") . ")";
		
		if ($words) {
			$words = array_unique($words);
			$weight = round(5 / count($words), 1);
			foreach ($words as $word) {
				$regex = array();
				$regex[] = $word;
				$regex[] = Jp7_Inflector::plural($word);
				$regex = array_unique($regex);
				//$match .= " + (" . reset($textColumns) . " LIKE '%" . $word . "%') * " . $weight;
				$match .= " + (" . reset($textColumns) . " REGEXP '(^|[^a-zA-Z])(" . addcslashes(implode('|', $regex), '[]()+?.') . ")([^a-zA-Z]|$)') * " . $weight;
			}
			//$match .= " + (CONCAT(" . implode(',', $textColumns) . ") LIKE '%" . addslashes($search) . "%') * 5";
		}
		
		$where[] = $this->getTipoFilter();
		$where[] = 'id_tipo > 0';
		
		if (!$s_session['deleted']) {
			$deleted_column = in_array('deleted', $columns) ? 'deleted' : '';
			if (!$deleted_column && in_array('deleted_tipo', $columns)) {
				$deleted_column = 'deleted_tipo';
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
			if (in_array('date_expire', $columns)) {
				$where[] = "(date_expire > '" . date('c') . "' OR date_expire = '0000-00-00 00:00:00')";			
			}
		}
		
		$sql = "SELECT " . implode(',', $fields) . ", "  . $match . " AS relevance " .
			"FROM `" . $table . "` " .
			"WHERE " . implode(' AND ', $where) . " " .
			"HAVING relevance > 0";
		return $sql;
	}
	/**
	 * SQL de permissões dos tipos.
	 * @return string
	 */
	public function getTipoFilter() {
		global $s_allowed_tipos;
		if (is_array($s_allowed_tipos)) {
			if ($s_allowed_tipos) {
				return "id_tipo IN (" . implode(',', $s_allowed_tipos) . ")";
			} else {
				return '0=1';
			}
		} else {
			return '1=1';
		}
	}
	
	public function isText($column) {
		return strpos($column, 'text') === 0 || strpos($column, 'varchar_') === 0 || strpos($column, 'nome') === 0;
	}
}