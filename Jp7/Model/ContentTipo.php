<?php

class Jp7_Model_ContentTipo extends Jp7_Model_TipoAbstract
{
    protected static $_children;

    public $attributes = [
        'type_id' => 'Content',
        'nome' => 'Conteúdo',
        'campos' => 'varchar_key{,}Título{,}{,}{,}{,}{,}0{,}{,}2{,}{,}{,}{,}{,}{,}{,}title{;}varchar_1{,}Subtítulo{,}{,}{,}{,}S{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}subtitle{;}text_1{,}Resumo{,}{,}3{,}{,}{,}html_light{,}{,}{,}{,}{,}{,}{,}{,}{,}summary{;}text_2{,}Texto{,}{,}20{,}{,}S{,}S{,}{,}{,}{,}{,}{,}{,}{,}{,}text{;}file_1{,}Imagem{,}{,}{,}{,}S{,}0{,}S{,}{,}{,}{,}{,}{,}{,}{,}image{;}int_key{,}Ordem{,}{,}{,}{,}{,}0{,}{,}1{,}{,}{,}{,}{,}{,}{,}ordem{;}char_key{,}Mostrar{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}mostrar{;}char_1{,}Destaque{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}featured{;}',
        'children' => '',
        'files_ajuda' => '',
        'files' => '',
        'template' => 'content/index',
        'editpage' => '',
        'class' => '',
        'class_type' => '',
        'model_type_id' => 0,
        'tabela' => '',
        'layout' => Jp7_Box_Manager::COL_2_LEFT,
        'layout_registros' => Jp7_Box_Manager::COL_2_LEFT,
        'editar' => 1,
    ];

    public function __construct()
    {
        parent::__construct();
        if (!self::$_children) {
            $contentSubitem = $this->_findChildByModel('ContentSubitem');
            $images = $this->_findChildByModel('Images');
            $videos = $this->_findChildByModel('ContentVideos');
            $contentFiles = $this->_findChildByModel('ContentFiles');

            self::$_children = $contentSubitem->type_id.'{,}Subitens{,}{,}{;}'.
                $images->type_id.'{,}Imagens{,}{,}{;}'.
                $videos->type_id.'{,}Vídeos{,}{,}{;}'.
                $contentFiles->type_id.'{,}Arquivos para Download{,}{,}{;}';
        }
        $this->children = self::$_children;
    }

    public function createChildren(InterAdminTipo $type)
    {
        parent::createBoxesSettingsAndIntroduction($type);
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
