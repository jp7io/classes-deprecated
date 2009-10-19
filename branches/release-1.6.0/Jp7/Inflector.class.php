<?php
/**
 * JP7's PHP Functions 
 * 
 * Contains the main custom functions and classes.
 * @author JP7
 * @copyright Copyright 2002-2009 JP7 (http://jp7.com.br)
 * @category Jp7
 * @package Jp7_Inflector
 */
 
/**
 * Inflector, used to generate names for classes, tables and others.
 *
 * @package Jp7_Inflector
 */
class Jp7_Inflector {
	
	public static function plural ($str) {
		if ($str[strlen($str) - 1] != 's') {
			$str .= 's';
		}
		return $str;
	}
	
	/**
	 * Converts from CamelCase to underscore_case.
	 * 
	 * @param object $str
	 * @return 
	 */
	public static function underscore ($str) {
		return strtolower(preg_replace('/([a-z])([A-Z])/', '\1_\2', $str));
	}
	
	
} 
