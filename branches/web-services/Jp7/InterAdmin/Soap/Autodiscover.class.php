<?php

class Jp7_InterAdmin_Soap_AutoDiscover extends Zend_Soap_AutoDiscover {
	
	public function getUsuario() {
		return $this->_reflection->getUsuario();
	}
	
	public function setUsuario(InterAdmin $usuario) {
		$this->_reflection = new Jp7_InterAdmin_Soap_Reflection($usuario);
	} 
	
}