<?php

class Jp7_Box_NewsArchive extends Jp7_Box_BoxAbstract
{
    /**
     * @see Jp7_Box_BoxAbstract::prepareData()
     */
    public function prepareData()
    {
        $type = $this->view->tipo;
        if ($type && $type->model_type_id == 'News') {
            $this->tipo = $type;
            $this->archives = $type->find([
                'fields' => ['publish_at'],
                'group' => 'MONTH(publish_at), YEAR(publish_at)',
                'order' => 'publish_at DESC',
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
