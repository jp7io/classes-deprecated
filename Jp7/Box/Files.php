<?php

class Jp7_Box_Files extends Jp7_Box_BoxAbstract
{
    /**
     * @see Jp7_Box_BoxAbstract::prepareData()
     */
    public function prepareData()
    {
        if ($this->view->record) {
            try {
                $this->files = $this->view->record->getArquivosParaDownload([
                    'fields' => ['name', 'file'],
                ]);
            } catch (Exception $e) {
                // Do nothing, method getArquivosParaDownload doesnt exist
            }
        } elseif ($this->view->tipo) {
            if ($filesTipo = $this->view->tipo->getFirstChildByModel('ContentFiles')) {
                $this->files = $filesTipo->find([
                    'fields' => ['name', 'file'],
                ]);
            }
        }
    }
    /**
     * @see Jp7_Box_BoxAbstract::_getEditorTitle()
     */
    protected function _getEditorTitle()
    {
        return 'Arq. para Download';
    }
}
