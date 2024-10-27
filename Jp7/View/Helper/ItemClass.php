<?php

class Jp7_View_Helper_ItemClass extends Zend_View_Helper_Abstract
{
    public function ItemClass($count, $key, $item = null)
    {
        $classes = [];
        if ($key == 0) {
            $classes[] = 'first-child';
        }
        if ($key + 1 == $count) {
            $classes[] = 'last-child';
        }
        if ($key % 2) {
            $classes[] = 'even';
        } else {
            $classes[] = 'odd';
        }
        if ($item instanceof InterAdmin) {
            $classes[] = 'ia-record';
            $classes[] = 'id-'.$item->id;
            $classes[] = 'tipo-'.$item->type_id;
        }
        //$classes[] = 'pos-' . ($key + 1);
        return implode(' ', $classes);
    }
}
