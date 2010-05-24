<?php

/**
 * É usado para simular um método no WebService.
 */
class Jp7_InterAdmin_Soap_ReflectionMethodGet {
	
	protected $secao;
		
	public function __construct(InterAdminTipo $secao) {
		$this->secao = $secao;
		$this->secao->getFieldsValues(array('nome', 'class'));
	} 
	
	/**
	 * @return array
	 */
	public function getPrototypes() {
		return array($this);
	}
	
	/**
	 * @return array
	 */
	public function getParameters() {
		return array(new Jp7_InterAdmin_Soap_ReflectionParameter());
	}
	
	/**
	 * @return string 
	 */
	public function getName() {
		return 'get' . Jp7_Inflector::plural($this->secao->class);
	}
	
	/**
	 * @return string 
	 */
	public function getReturnType() {
		return $this->secao->class . '[]';
	}
	
	public function getDescription() {
		return utf8_encode('Retorna todos os registros da seção ' . $this->secao->nome);
	}
}