<?php

class Jp7_Box_News extends Jp7_Box_BoxAbstract {    /**
     * @see Jp7_Box_BoxAbstract::prepareData()
     */
    public function prepareData() {
    	// TODO Jeito melhor de pegar RootTipo?
    	$defaultParentClass = Jp7_Controller_Dispatcher::getDefaultParentClass();
		$rootTipo = call_user_func(array($defaultParentClass, 'getRootTipo'));
		
    	$newsTipo = reset($rootTipo->findTipos(array(
			'where' => array("model_id_tipo = 'News'"),
			'limit' => 1
		)));
		
		$this->news = $newsTipo->getInterAdmins(array(
			'fields' => array('titulo', 'date_publish'),
			'limit' => 3
		));
    }
   
}