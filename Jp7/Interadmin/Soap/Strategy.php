<?php

class Jp7_Interadmin_Soap_Strategy extends  Zend_Soap_Wsdl_Strategy_ArrayOfTypeSequence
{
    protected $_inProgress = [];

    protected function _appendElements($dom, $container, $elements)
    {
        foreach ($elements as $name => $type) {
            $element = $dom->createElement('xsd:element');
            //$element->setAttribute('minOccurs', '0');
            $element->setAttribute('name', $name);
            $element->setAttribute('nillable', 'true');
            $element->setAttribute('type', $type);
            $container->appendChild($element);
        }
    }

    public function addComplexType($type)
    {
        if (!in_array($type, $this->getContext()->getTypes())) {
            $isDynamicClass = Jp7_Interadmin_Soap::isDynamicClass($type);
            if (mb_substr($type, mb_strlen($type) - 2) == '[]' || ($type != 'Options' && !$isDynamicClass && !is_subclass_of($type, 'InterAdminAbstract'))) {
                try {
                    return parent::addComplexType($type);
                } catch (Zend_Soap_Wsdl_Exception $e) {
                    // Possibly a class was set but was not created a file for it
                    Log::error($e);
                }
            }

            // Evitar looping infinito
            if (!in_array($type, $this->_inProgress)) {
                $this->_inProgress[] = $type;

                $dom = $this->getContext()->toDomDocument();

                $complexType = $dom->createElement('xsd:complexType');
                $complexType->setAttribute('name', $type);
                $all = $dom->createElement('xsd:all');

                // Jp7
                if ($type == 'Options') {
                    $this->_appendElements($dom, $all, [
                        'fields' => 'xsd:string',
                        'where' => 'xsd:string',
                        'limit' => 'xsd:string',
                    ]);
                } else {
                    if ($isDynamicClass || is_subclass_of($type, 'InterAdmin')) {
                        $type = Jp7_Interadmin_Soap::getClassTipo($type);

                        $type->getFieldsAlias();
                        $campos = $type->getFields();

                        $elements = [];
                        foreach ($campos as $campo) {
                            if (strpos($campo['tipo'], 'tit_') === false && strpos($campo['tipo'], 'func_') === false) {
                                $elements[$campo['nome_id']] = $this->_getCampoType($type, $campo);
                            }
                        }

                        $elements += [
                            'id' => 'xsd:int',
                            'type_id' => 'xsd:int',
                            'parent_id' => 'xsd:int',
                            'date_insert' => 'xsd:dateTime',
                            'date_modify' => 'xsd:dateTime',
                            'date_publish' => 'xsd:dateTime',
                            'deleted' => 'xsd:boolean',
                            'publish' => 'xsd:boolean',
                        ];

                        $this->_appendElements($dom, $all, $elements);
                    } else {
                        // InterAdminTipo
                        $this->_appendElements($dom, $all, [
                            'type_id' => 'xsd:int',
                            'nome' => 'xsd:string',
                            'parent_type_id' => 'xsd:int',
                            'model_type_id' => 'xsd:int',
                            'class' => 'xsd:string',
                            'class_type' => 'xsd:string',
                            'deleted_type' => 'xsd:boolean',
                            'mostrar' => 'xsd:boolean',
                        ]);
                    }
                }

                $complexType->appendChild($all);
                $this->getContext()->getSchema()->appendChild($complexType);
                $this->getContext()->addType($type);
            }

            return "tns:$type";
        } else {
            // Existing complex type
            return $this->getContext()->getType($type);
        }
    }

    protected function _getCampoType($type, $campo)
    {
        if (strpos($campo['tipo'], 'special_') === 0 && $campo['xtra']) {
            $isMulti = in_array($campo['xtra'], InterAdminField::getSpecialMultiXtras());
            $isTipo = in_array($campo['xtra'], InterAdminField::getSpecialTipoXtras());

            $retorno = $this->_getCampoTypeClass($type->getCampoTipo($campo), $isTipo, $isMulti);
        } elseif (strpos($campo['tipo'], 'select_') === 0) {
            $isMulti = (strpos($campo['tipo'], 'select_multi') === 0);
            $isTipo = in_array($campo['xtra'], InterAdminField::getSelectTipoXtras());

            $retorno = $this->_getCampoTypeClass($campo['nome'], $isTipo, $isMulti);
        } elseif (strpos($campo['tipo'], 'int') === 0 || strpos($campo['tipo'], 'id') === 0) {
            $retorno = 'int';
        } elseif (strpos($campo['tipo'], 'char') === 0) {
            $retorno = 'boolean';
        } elseif (strpos($campo['tipo'], 'date') === 0) {
            return 'xsd:dateTime';
        } else {
            $retorno = 'string';
        }

        return $this->getContext()->getType($retorno);
    }
    /**
     * Helper da função _getCampoType.
     *
     * @param InterAdminTipo $campoTipo
     * @param bool           $isTipo
     * @param bool           $isMulti
     *
     * @return string Type para o WSDL a partir da classe que está no $campoTipo->class.
     */
    protected function _getCampoTypeClass($campoTipo, $isTipo, $isMulti)
    {
        if ($isTipo) {
            $retorno = 'InterAdminTipo';
        } else {
            $retorno = $campoTipo->class;
        }
        if ($isMulti && $retorno) {
            $retorno .= '[]';
        }
        if (!$retorno) {
            $retorno = 'int';
        }

        return $retorno;
    }

     /**
      * Append the complex type definition to the WSDL via the context access.
      *
      * @param  string $arrayType
      * @param  string $childTypeName
      */
     protected function _addElementFromWsdlAndChildTypes($arrayType, $childTypeName)
     {
         /* Código da ZEND - Não Alterar */
        if (!in_array($arrayType, $this->getContext()->getTypes())) {
            $dom = $this->getContext()->toDomDocument();

            $complexType = $dom->createElement('xsd:complexType');
            $complexType->setAttribute('name', $arrayType);

            $sequence = $dom->createElement('xsd:sequence');

            $element = $dom->createElement('xsd:element');
            $element->setAttribute('name',      'itens'); /* LINHA ALTERADA PELA Jp7*/
            $element->setAttribute('type',      $childTypeName);
            $element->setAttribute('minOccurs', 0);
            $element->setAttribute('maxOccurs', 'unbounded');
            $sequence->appendChild($element);

            $complexType->appendChild($sequence);

            $this->getContext()->getSchema()->appendChild($complexType);
            $this->getContext()->addType($arrayType);
        }
     }
}
