<?php

/**
 * É usado para simular um método no WebService.
 */
class Jp7_Interadmin_Soap_ReflectionMethodGet
{
    protected $secao;

    public function __construct(InterAdminTipo $secao)
    {
        $this->secao = $secao;
    }

    /**
     * @return array
     */
    public function getPrototypes()
    {
        return [$this];
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return [
            new Jp7_Interadmin_Soap_ReflectionParameter('options', 'Options'),
        ];
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'get'.$this->_getClassName();
    }

    /**
     * @return string
     */
    public function getReturnType()
    {
        return $this->_getClassName().'[]';
    }

    public function getDescription()
    {
        return 'Retorna os registros publicados e não deletados da seção '.$this->secao->nome.'.';
    }

    protected function _getClassName()
    {
        return ($this->secao->class) ? $this->secao->class : Jp7_Inflector::camelize($this->secao->nome).'_'.$this->secao->type_id;
    }
}
