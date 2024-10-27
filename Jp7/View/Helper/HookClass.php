<?php

class Jp7_View_Helper_HookClass extends Zend_View_Helper_Abstract
{
    public function HookClass($item)
    {
        $classes = [];
        $classes[] = 'ia-record';
        $classes[] = 'id-'.$item->id;
        $classes[] = 'tipo-'.$item->type_id;

        return implode(' ', $classes);
    }
}
