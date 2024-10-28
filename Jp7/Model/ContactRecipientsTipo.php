<?php

class Jp7_Model_ContactRecipientsTipo extends Jp7_Model_TipoAbstract
{
    public $hasOwnPage = false;
    public $isSubTipo = true;

    public $attributes = [
        'type_id' => 'ContactRecipients',
        'nome' => 'Contato - Destinatários',
        'campos' => 'varchar_key{,}Nome{,}{,}{,}{,}{,}0{,}{,}2{,}{,}{,}{,}{,}{,}{,}name{;}varchar_1{,}E-mail{,}{,}{,}S{,}S{,}email{,}S{,}{,}{,}{,}{,}{,}{,}{,}email{;}int_key{,}Ordem{,}{,}{,}{,}{,}0{,}{,}1{,}{,}{,}{,}{,}{,}{,}ordem{;}char_key{,}Mostrar{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}mostrar{;}',
        'children' => '',
        'files_ajuda' => '',
        'files' => '',
        'template' => '',
        'editpage' => '',
        'class' => '',
        'class_type' => '',
        'model_type_id' => 0,
        'tabela' => '',
        'texto' => 'Usuários que receberão um e-mail de aviso a cada mensagem recebida através da página de contato.',
        'editar' => 'S',
        'icone' => 'email',
    ];
}
