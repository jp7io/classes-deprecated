<?php

class Jp7_Model_SlideshowTipo extends Jp7_Model_TipoAbstract
{
    public $hasOwnPage = false;

    public $attributes = [
        'type_id' => 'Slideshow',
        'nome' => 'Slideshow',
        'campos' => 'varchar_key{,}Nome{,}{,}{,}{,}S{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}name{;}file_1{,}Imagem{,}{,}{,}S{,}S{,}trigger{,}S{,}{,}{,}{,}{,}{,}{,}{,}image{;}varchar_1{,}Link{,}{,}{,}S{,}S{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}link{;}text_2{,}Título{,}{,}2{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}title{;}text_1{,}Texto{,}{,}2{,}{,}S{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}text{;}int_key{,}Ordem{,}{,}{,}{,}{,}0{,}{,}1{,}{,}{,}{,}{,}{,}{,}ordem{;}char_key{,}Mostrar{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}mostrar{;}',
        'children' => '',
        'files_ajuda' => '',
        'files' => '',
        'template' => '',
        'editpage' => '',
        'class' => '',
        'class_type' => '',
        'model_type_id' => 0,
        'tabela' => '',
        'editar' => 'S',
        'icone' => 'application_view_gallery',
    ];
}
