<?php 

/**
 * Helper for date utils.
 * 
 * @package Jp7
 */
class Jp7_Date extends DateTime {
	/**
	 * Retorna string da diferença de tempo, ex: '3 dias atrás'.
	 * O valor é arredondado: 2 anos e 4 meses retorna '2 anos atrás'.
	 * Diferenças menores de 1 minuto retornam 'agora'.
	 * 
	 * @param int|string $timeStamp Timestamp ou Datetime. 
	 * @return string
	 */
	public static function humanDiff($timeStamp) {
		if (!is_int($timeStamp)) {
			$timeStamp = strtotime($timeStamp);
		}
		$currentTime = time();
		$units = array(
			'ano' => 31556926,
			'mês' => 2629743,
			'semana' => 604800,
			'dia' => 86400,
			'hora' => 3600,
			'minuto' => 60
		);
		$seconds = $currentTime - $timeStamp;
		if ($seconds <= 60) {
			return 'agora';
		}
		foreach ($units as $unit => $seconds_in_period) {
			if ($seconds >= $seconds_in_period) {
				$count = floor($seconds / $seconds_in_period);
				$unit = Jp7_Inflector::plural($unit, $count);
				return $count . ' ' . $unit . ' atrás';
			}
		}
	}
	
	/**
	 * Returns the age based on the birthdate and the current date.
	 * 
	 * @param string|int $birth Datetime (string) or Timestamp (int).
	 * @param string|int $now [optional]
	 * @return int Age in years.
	 */
	public static function yearsDiff($birth, $now = null) {
		// Override
		if (is_integer($birth)) {
			$birth = date('Y-m-d', $birth);
		}
		if (is_null($now)) {
			$now = time();
		} else {
			$now = self::toTime($now);
		}
		// Function itself
		list($y, $m, $d) = explode('-', $birth);
		$years = date('Y', $now) - $y;
		if (date('md', $now) < $m . $d) {
			$years--;
		}
		return $years;
	}
	
	/**
	 * Difference of days between 2 timestamps.
	 * 
	 * @param int $from
	 * @param int $to [optional]
	 * @return int
	 */
	public static function daysDiff($from, $to = null) {
		$from = self::toTime($from);
		if (is_null($to)) { 
			$to = time();
		} else {
			$to = self::toTime($to);
		}
		$diff = $to - $from;
		$days = round($diff / 86400);
		return $days;
	}
	
	/**
	 * Converts string to time if needed.
	 * 
	 * @param string $datetime
	 * @return int 
	 */
	public static function toTime($datetime) {
		if (is_string($datetime)) {
			$datetime = strtotime($datetime);
		}
		return $datetime;
	}
	
	/**
["y"]=>  int(2)
["m"]=>  int(0) 
["d"]=>  int(1) 
["h"]=>  int(0) 
["i"]=>  int(0)
["s"]=>  int(0)
["invert"]=>  int(0) 
["days"]=>  int(6015) 
	
	$d1 = new DateTime('1992-01-01');
	$d2 = new DateTime('1992-12-31');
	var_dump(class_exists('DateInterval'));
	
	var_dump($d1->diff($d2));
    */
	public function diff(Jp7_Date $datetime) {
		if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
			// Versão 5.3 já possui método
			$retorno = parent::diff($datetime);
		} else {
			// Versões antigas precisam fazer "manualmente"
			$keys = array('y', 'm', 'd', 'h');
			if ($this < $datetime){
				$temp = $datetime->getTimestamp();
				$d1 = date_parse($datetime->format('Y-m-d H:i:s'));
				$d2 = date_parse($this->format('Y-m-d H:i:s'));
			} else {
				$temp = $this->getTimestamp();
			    $d1 = date_parse($this->format('Y-m-d H:i:s'));
				$d2 = date_parse($datetime->format('Y-m-d H:i:s'));
			}
			
			
			krumo($d1);
			die();
			if ($d1['s'] >= $d2['s']) {
				$diff['s'] = $d1['s'] - $d2['s'];
			} else {
				$d1['i']--;
				$diff['s'] = 60 - $d2['s'] + $d1['s'];
			}
			if ($d1['i'] >= $d2['i']){
				$diff['i'] = $d1['i'] - $d2['i'];
			} else {
				$d1['h']--;
				$diff['i'] = 60 - $d2['i'] + $d1['i'];
			}
			if ($d1['h'] >= $d2['h']) {
				$diff['h'] = $d1['h'] - $d2['h'];
			} else {
				$d1['d']--;
				$diff['h'] = 24 - $d2['h'] + $d1['h'];
			}
			if ($d1['d'] >= $d2['d']) {
				$diff['d'] = $d1['d'] - $d2['d'];
			} else {
				$d1['m']--;
				$diff['d'] = date('t', $temp) - $d2['d'] + $d1['d'];
			}
			if ($d1['m'] >= $d2['m']) {
				$diff['m'] = $d1['m'] - $d2['m'];
			} else {
				$d1['y']--;
				$diff['m'] = 12 - $d2['m'] + $d1['m'];
			}
			$diff['y'] = $d1['y'] - $d2['y'];
			$retorno = $diff;
		}
		//$retorno->days = '';
		return $retorno;
	}
	
	/**
	 * Gets the Unix timestamp
	 * 
	 * @return int Returns Unix timestamp representing the date. 
	 */
	public function getTimestamp() {
		if (version_compare(PHP_VERSION, '5.3.0', '>=')) {
			return parent::getTimestamp();
		} else {
			return $this->format('U');
		}
	}
	
	/**
	 * Returns date formatted according to given format.
	 * 
	 * @param string $format Format accepted by date().
	 * @return 
	 */
	public function format($format) {
		if (strpos($format, 'M') !== false) {
			$format = preg_replace('/M/', addcslashes(jp7_date_month($this->format('m'), true), 'A..z'), $format);
		}
		if (strpos($format, 'F') !== false) {
			$format = preg_replace('/F/', addcslashes(jp7_date_month($this->format('m')), 'A..z'), $format);
		}
		return parent::format($format);
	}
	
	public function __toString() {
		return $this->format('c');
	}
	
	public function minute() {
		return $this->format('i');
	}
	public function hour() {
		return $this->format('H');
	}
	public function day() {
		return $this->format('d');
	}
	public function month() {
		return $this->format('m');
	}
	public function quarter() {
		return ceil($this->format('m') / 3);
	}
	public function year() {
		return $this->format('Y');
	}
}