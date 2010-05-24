<?php

/**
 * É usado para simular um parâmetro de cada método no WebService.
 */
class Jp7_InterAdmin_Soap_ReflectionParameter {

	/**
	 * @return string 
	 */
	public function getName() {
		return 'query';
	}
	
	public function getType() {
		return 'string';
	}
	
	public function isOptional() {
		return true;
	}
}