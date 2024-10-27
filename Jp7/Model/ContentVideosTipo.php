<?php

class Jp7_Model_ContentVideosTipo extends Jp7_Model_TipoAbstract
{
    public $hasOwnPage = false;
    public $isSubTipo = true;

    public $attributes = [
        'type_id' => 'ContentVideos',
        'nome' => 'Conteúdo - Vídeos',
        'campos' => 'varchar_key{,}Título{,}{,}{,}S{,}S{,}0{,}{,}2{,}{,}{,}{,}{,}{,}{,}title{;}varchar_1{,}Vídeo{,}Endereço do vídeo no YouTube ou Vimeo. Ex: http://www.youtube.com/watch?v=123ab456{,}{,}S{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}video{;}file_1{,}Thumb{,}Caso não seja cadastrada, será usada a imagem do YouTube para preview do vídeo.{,}{,}{,}S{,}0{,}S{,}{,}{,}{,}{,}{,}{,}{,}thumb{;}varchar_2{,}Duração{,}{,}{,}{,}S{,}0{,}S{,}{,}{,}{,}{,}{,}{,}{,}duration{;}text_1{,}Descrição{,}{,}5{,}{,}S{,}S{,}{,}{,}{,}{,}{,}{,}{,}{,}summary{;}int_key{,}Ordem{,}{,}{,}{,}S{,}0{,}{,}1{,}{,}{,}{,}{,}{,}{,}ordem{;}char_key{,}Mostrar{,}{,}{,}{,}{,}S{,}{,}{,}{,}{,}{,}{,}{,}{,}mostrar{;}char_1{,}Destaque{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}featured{;}',
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
        'texto' => 'Cadastro de vídeos do YouTube e Vimeo.',
        'disparo' => 'Jp7_Model_VideosTipo::checkThumb',
        'icone' => 'film',
    ];
}
