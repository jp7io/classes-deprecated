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
	
	public static $plural_inflections = array(
		'(m)$' => 'ns',
		'([r|z])$' => '\1es',
		'([i])l$' => '\1s',
		'([a|e|o|u])l$' => '\1is',
		'([^s])$' => '\1s'
	);
	
	/**
	 * Returns the plural form of the word in the string.
	 * 
	 * @param object $word
	 * @return string
	 */
	public static function plural ($word) {
		foreach (self::$plural_inflections as $pattern => $replacement) {
			if (preg_match('/' . $pattern . '/', $word)) {
				$word = preg_replace('/' . $pattern . '/', $replacement, $word);
				break;
			}
		}
		return $word;
	}
	
	/**
	 * Converts from CamelCase to underscore_case.
	 * 
	 * @param object $camelCasedWord
	 * @return string
	 */
	public static function underscore ($camelCasedWord) {
		return strtolower(preg_replace('/([a-z])([A-Z])/', '\1_\2', $camelCasedWord));
	}
	
	
} 
