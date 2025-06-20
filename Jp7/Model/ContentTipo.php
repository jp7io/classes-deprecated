<?php

class Jp7_Model_ContentTipo extends Jp7_Model_TipoAbstract
{
    protected static $_children;

    public $attributes = [
        'id_tipo' => 'Content',
        'nome' => 'Conteúdo',
        'campos' => 'varchar_key{,}Título{,}{,}{,}{,}{,}0{,}{,}2{,}{,}{,}{,}{,}{,}{,}title{;}varchar_1{,}Subtítulo{,}{,}{,}{,}S{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}subtitle{;}text_1{,}Resumo{,}{,}3{,}{,}{,}html_light{,}{,}{,}{,}{,}{,}{,}{,}{,}summary{;}text_2{,}Texto{,}{,}20{,}{,}S{,}S{,}{,}{,}{,}{,}{,}{,}{,}{,}text{;}file_1{,}Imagem{,}{,}{,}{,}S{,}0{,}S{,}{,}{,}{,}{,}{,}{,}{,}image{;}int_key{,}Ordem{,}{,}{,}{,}{,}0{,}{,}1{,}{,}{,}{,}{,}{,}{,}ordem{;}char_key{,}Mostrar{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}mostrar{;}char_1{,}Destaque{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}featured{;}',
        'children' => '',
        'arquivos_ajuda' => '',
        'arquivos' => '',
        'template' => 'content/index',
        'editpage' => '',
        'class' => '',
        'class_tipo' => '',
        'model_id_tipo' => 0,
        'tabela' => '',
        'layout' => Jp7_Box_Manager::COL_2_LEFT,
        'layout_registros' => Jp7_Box_Manager::COL_2_LEFT,
        'editar' => 'S',
    ];

    public function __construct()
    {
        parent::__construct();
        if (!self::$_children) {
            $contentSubitem = $this->_findChildByModel('ContentSubitem');
            $images = $this->_findChildByModel('Images');
            $videos = $this->_findChildByModel('ContentVideos');
            $contentFiles = $this->_findChildByModel('ContentFiles');

            self::$_children = $contentSubitem->id_tipo.'{,}Subitens{,}{,}{;}'.
                $images->id_tipo.'{,}Imagens{,}{,}{;}'.
                $videos->id_tipo.'{,}Vídeos{,}{,}{;}'.
                $contentFiles->id_tipo.'{,}Arquivos para Download{,}{,}{;}';
        }
        $this->children = self::$_children;
    }

    public function createChildren(InterAdminTipo $tipo)
    {
        parent::createBoxesSettingsAndIntroduction($tipo);
    }

    public function getEditorFields(Jp7_Box_BoxAbstract $box)
    {
        ob_start();
        ?>
		<div class="fields">
			<?php echo parent::_getEditorImageFields($box, true);
        ?>
		</div>
		<?php
        return ob_get_clean();
    }

    public function prepareData(Jp7_Box_BoxAbstract $box)
    {
        parent::_prepareImageData($box);
    }
}
