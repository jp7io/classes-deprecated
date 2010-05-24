<?php

class Jp7_InterAdmin_Soap {
	
//	/**
//	 * Returns an array of records.
//	 * 
//	 * @param string $className
//	 * @param InterAdmin_Soap_Options $options
//	 * @return mixed
//	 */
//	public function getFirstInterAdmin($className, InterAdmin_Soap_Options $options = null) {
//		if (!class_exists($className)) {
//			throw new InterAdmin_Soap_Exception("Class Not Found: $className" );
//		}
//		$tipo = new $className();
//		if (!$tipo instanceof InterAdminTipo) {
//			throw new InterAdmin_Soap_Exception("Class should extend InterAdminTipo, invalid class given: $className" );
//		}
//		
//		if ($options) {
//			$options = $options->getArray();
//		} else {
//			$options = array();
//		}
//		
//		$interAdmin = $tipo->getFirstInterAdmin($options);
//		
//		foreach ($interAdmin->attributes as $key => $value) {
//			if ($value instanceof InterAdminAbstract) {
//				$interAdmin->attributes[$key] = $value->attributes;
//			}
//		}
//		
//		return $interAdmin->attributes;
//	}
	
	/**
	 * Returns a Ciintranet_Usuario.
	 *
	 * @return Ciintranet_Usuario
	 */
	public function getFirstCintranet_Usuario() {
		$tipo = new Ciintranet_UsuarioTipo();
		$usuario = $tipo->getFirstInterAdmin();
		
		foreach ($usuario->attributes as $key => $value) {
			if ($value instanceof InterAdminAbstract) {
				$usuario->attributes[$key] = $value->attributes;
			}
		}
		
		return $usuario->attributes;
	}
}