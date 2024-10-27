<?php

class Jp7_View_Helper_PageClass extends Zend_View_Helper_Abstract
{
    public function PageClass()
    {
        $classes = [];
        if ($this->view->tipo) {
            $classes[] = 'page-'.toSeo($this->view->tipo->nome);
            $classes[] = 'tipo-'.$this->view->tipo->type_id;
        }

        return implode(' ', $classes);
    }
}
