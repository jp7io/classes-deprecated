<?php
class Jp7_Collections {
 	/**
	 * Acts like a SELECT statement. Performs 'where', 'order', 'group' and 'limit'.
	 * 
	 * @param array $array
	 * @param array $options Available keys are 'where', 'order', 'group' and 'limit'.
	 * @return array Processed collection.
	 */
	public static function query($array, $options) {
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
		return $array;
	}
	/**
	 * Acts like a SQL GROUP BY  statement.
	 *
	 * @param array $array
	 * @param string $clause
	 * @return array
	 */
	public static function group($array, $clause) {
		// @todo
	}
	/**
	 * Filters the array using SQL Where.
	 * 
	 * @param array $array
	 * @param string $clause Similar to SQL WHERE Clause.
	 * @return array
	 */
	public static function filter($array, $clause) {
		// $callback = 'fazer';
		// array_filter($ary, $callback);
	}
	/**
 	 * Acts like an order by on an SQL.
 	 * 
     * @param array $array The array we want to sort.
     * @param string $clause A string specifying how to sort the array similar to SQL ORDER BY clause.
     * @return array 
     */ 
    public static function sort($array, $clause) {
        $dirMap = array('desc' => 1, 'asc' => -1); 
		
		$clause = preg_replace('/\s+/', ' ', $clause);
        $keys = explode(',', $clause); 
       	
	    $retorno = 'return 0;';
		
        for ($i = count($keys) - 1; $i >= 0; $i--) {
            list($k, $dir) = explode(' ', trim($keys[$i]));
			
			if ($dir) {
				$t = $dirMap[strtolower($dir)];
			} else {
				$t = $dirMap['asc'];
			}
            $f = -1 * $t;
			
			if ($k == 'RAND()') {
            	$aStr = $bStr = 'rand()';
			} else {
				$aStr = '$a->' . $k;
            	$bStr = '$b->' . $k;
			}
			
            $fnBody = 'if (' . $aStr . ' == ' . $bStr . ') {' . //"\n" .
					$retorno . //"\n" .
				' } ' . //"\n" .
				'return (' . $aStr . ' < ' . $bStr . ') ? ' . $t . ' : ' . $f . ';';// . "\n";
			$retorno = &$fnBody;
        }
        if ($fnBody) {
            usort($array, create_function('$a,$b', $fnBody));        
        }
		return $array;
    }
	/**
	 * Acts like a LIMIT statement on SQL.
	 * 
	 * @param array &$array
	 * @param string $clause Similar to SQL LIMIT clause.
	 * @return array
	 */
	public static function slice($array, $clause) {
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

}