<?php

class Jp7_Box_Manager {    /**
     * @var array
     */
	private static $array = array(
		'facebook' => 'Jp7_Box_Facebook'		
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
	
	public static function buildBoxes($records) {
		$cols = array();
		foreach ($records as $record) {
			if ($classe = self::get($record->id_box)) {
				$box = new $classe($record);
				if (!$box instanceof Jp7_Box_BoxAbstract) {
					throw new Exception('Expected an instance of Jp7_Box_BoxAbstract, received a ' . get_class($box) . '.');
				}
				$box->prepareData();
				$cols[$record->column][] = $box;
			}
		}
		//ksort($cols);
		end($cols);
		$mask = array_fill(0, key($cols) + 1, array());
		$cols += $mask;
		ksort($cols);
		return $cols;
	}
}