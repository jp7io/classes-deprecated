<?php

class Jp7_Form_View extends Zend_View
{
    public function __construct($config = [])
    {
        parent::__construct($config);
        $this->addHelperPath('Jp7/View/Helper', 'Jp7_View_Helper');
    }
}
