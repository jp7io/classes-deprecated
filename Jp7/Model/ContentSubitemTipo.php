<?php

class Jp7_Model_ContentSubitemTipo extends Jp7_Model_TipoAbstract
{
    public $isSubTipo = true;

    public $attributes = [
        'type_id' => 'ContentSubitem',
        'nome' => 'Conteúdo - Subitens',
        'campos' => 'varchar_key{,}Título{,}{,}{,}{,}{,}0{,}{,}2{,}{,}{,}{,}{,}{,}{,}title{;}varchar_1{,}Subtítulo{,}{,}{,}{,}S{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}subtitle{;}text_2{,}Texto{,}{,}20{,}{,}S{,}S{,}{,}{,}{,}{,}{,}{,}{,}{,}text{;}file_1{,}Imagem{,}{,}{,}{,}S{,}0{,}S{,}{,}{,}{,}{,}{,}{,}{,}image{;}int_key{,}Ordem{,}{,}{,}{,}{,}0{,}{,}1{,}{,}{,}{,}{,}{,}{,}ordem{;}char_key{,}Mostrar{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}mostrar{;}',
        'children' => '',
        'files_ajuda' => '',
        'files' => '',
        'template' => '',
        'editpage' => '',
        'class' => '',
        'class_tipo' => '',
        'model_type_id' => 0,
        'tabela' => '',
        'editar' => 'S',
    ];
}
