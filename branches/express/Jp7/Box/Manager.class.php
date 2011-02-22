<?php

class Jp7_Box_Manager {    /**
     * @var array
     */
	private static $array = array(
		'facebook' => 'Jp7_Box_Facebook'		
	);
	
	private static $config = array(
		110 => array(2)
	);
		
	/**
	 * Private constructor.
	 */
	private function __construct() {
		// Static class
	}
    /**
     * Returns $array.
     *
     * @see Jp7_Box_Manager::$array
     */
    public static function getArray() {
        return self::$array;
    }
	/**
	 * Sets a classname to the given box id.
	 * @return void
	 */
	public static function set($id, $className) {
		self::$array[$id] = $className;
	}
	/**
	 * Gets the classname for the given box id.
	 * @return string
	 */
	public static function get($id) {
		return self::$array[$id];
	}
	
	public static function remove($id) {
		unset(self::$array[$id]);
	}
	
	public static function buildBoxes($columns, $prepareData = true) {
		foreach ($columns as $column) {
			$records = $column->getBoxes(array(
				'fields' => array('*'),
				'fields_alias' => true,
				'use_published_filters' => true
			));
			$column->boxes = array();
			foreach ($records as $record) {
				if ($classe = self::get($record->id_box)) {
					$box = new $classe($record);
					if (!$box instanceof Jp7_Box_BoxAbstract) {
						throw new Exception('Expected an instance of Jp7_Box_BoxAbstract, received a ' . get_class($box) . '.');
					}
					if ($prepareData) {
						$box->prepareData();
					}
					$column->boxes[] = $box;
				}
			}
		}
		return $columns;
	}
	
	public static function setConfig($config) {
		self::$config = $config;
	}
}