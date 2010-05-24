<?php

class Jp7_InterAdmin_Soap {
	/**
	 * Returns an array of records.
	 * 
	 * @param string $className
	 * @param array $options
	 * @return mixed
	 */
	protected function get($className, $options = array()) {
		$tipoName = $className . 'Tipo';
		if (!class_exists($className) || !class_exists($tipoName)) {
			throw new Jp7_InterAdmin_Soap_Exception("Class is not supported: $className");
		}
		
		$tipo = new $tipoName();
		
		$records = $tipo->getInterAdmins($options);
		foreach ($records as $key => $record) {
			foreach ($record->attributes as $key2 => $value) {
				if ($value instanceof InterAdminAbstract) {
					$record->attributes[$key2] = $value->attributes;
				}
			}
			$records[$key] = $record->attributes;
		}
		
		return $records;
	}
	
	/**
	 * Returna o primeiro registro.
	 * 
	 * @param string $className
	 * @param array $options
	 * @return mixed
	 */
	protected function getFirst($className, $options = array()) {
		$options['limit'] = 1;
		$records = $this->get($className, $options);
		return reset($records);
	}
	
	public function __call($methodName, $args) {
		if (strpos($methodName, 'get') === 0) {
			if ($args[0]) {
				$options = array(
					'fields' => jp7_explode(',', $args[0]->fields),
					'where' => jp7_explode(',', $args[0]->where),
					'limit' => $args[0]->limit
				);
			}
			
			if (strpos($methodName, 'getFirst') === 0) {
				$className = substr($methodName, strlen('getFirst'));
				$result = $this->getFirst($className, $options);
			} else {
				$className = substr($methodName, strlen('get'));
				$result = $this->get($className, $options);
			}
		}
		return array($methodName . 'Result' => $result);
	}
}