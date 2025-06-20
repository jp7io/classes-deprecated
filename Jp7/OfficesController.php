<?php

// Necessário para herdar métodos padrão
return Jp7_Controller_Dispatcher::evalAsAController(__FILE__);

class Jp7_OfficesController extends __Controller_Action
{
    public function indexAction()
    {
        $id = $this->_getParam('id');
        // Irá cachear uma página diferente para cada registro
        Jp7_Cache_Output::getInstance()->start((string) $id);

        $officesTipo = self::getTipo();

        $this->view->headScript()->appendFile('//maps.google.com/maps/api/js?sensor=true');
        $this->view->headScript()->appendFile(DEFAULT_PATH.'/js/jquery/jquery.jp7.js');

        if ($id) {
            $record = $officesTipo->findById($id, [
                'fields' => ['*', 'state' => ['sigla']],
            ]);
            if (!$record) {
                $this->_redirect($officesTipo->getUrl());
            }
            self::setRecord($record);
        } else {
            // Introdução
            if ($introductionTipo = $officesTipo->getFirstChildByModel('Introduction')) {
                $this->view->introductionItens = $introductionTipo->find([
                    'fields' => '*',
                ]);
            }

            $this->view->records = $officesTipo->find([
                'fields' => ['*', 'state' => ['sigla']],
            ]);
        }
    }
}
