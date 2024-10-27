<?php

class Jp7_Box_NewsArchive extends Jp7_Box_BoxAbstract
{
    /**
     * @see Jp7_Box_BoxAbstract::prepareData()
     */
    public function prepareData()
    {
        $tipo = $this->view->tipo;
        if ($tipo && $tipo->model_type_id == 'News') {
            $this->tipo = $tipo;
            $this->archives = $tipo->find([
                'fields' => ['date_publish'],
                'group' => 'MONTH(date_publish), YEAR(date_publish)',
                'order' => 'date_publish DESC',
            ]);
        }
    }
    /**
     * @see Jp7_Box_BoxAbstract::_getEditorTitle()
     */
    protected function _getEditorTitle()
    {
        return 'Histórico de Notícias';
    }
}
