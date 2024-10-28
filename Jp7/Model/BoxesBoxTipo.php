<?php

class Jp7_Model_BoxesBoxTipo extends Jp7_Model_TipoAbstract
{
    public $isSubTipo = true;

    public $attributes = [
        'type_id' => 'BoxesBox',
        'nome' => 'Boxes - Box',
        'campos' => 'varchar_key{,}ID do Box{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}id_box{;}text_1{,}Parâmetros{,}{,}10{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}params{;}char_key{,}Mostrar{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}mostrar{;}int_key{,}Ordem{,}{,}{,}{,}{,}0{,}{,}1{,}{,}{,}{,}{,}{,}{,}ordem{;}',
        'children' => '',
        'files_ajuda' => '',
        'files' => '',
        'template' => '',
        'editpage' => '',
        'class' => '',
        'class_type' => '',
        'model_type_id' => 0,
        'tabela' => 'boxes',
    ];
}
