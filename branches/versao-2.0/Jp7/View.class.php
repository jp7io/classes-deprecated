<?php

class Jp7_View extends Zend_View {
	public function __construct($config = array())
    {
    	parent::__construct($config);
		$this->addHelperPath('Jp7/View/Helper', 'Jp7_View_Helper');
    }
	
	protected function _run()
    {
        if ($this->_useViewStream && $this->useStreamWrapper()) {
			include 'zend.view://' . func_get_arg(0);
        } else {
        	$filename = func_get_arg(0); 
        	echo $filename;
            include $filename;
        }
    }
}