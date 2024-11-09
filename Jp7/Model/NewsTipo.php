<?php

class Jp7_Model_NewsTipo extends Jp7_Model_TipoAbstract
{
    protected static $_children;

    public $attributes = [
        'type_id' => 'News',
        'nome' => 'Notícias',
        'campos' => 'varchar_key{,}Título{,}{,}{,}S{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}title{;}varchar_1{,}Subtítulo{,}{,}{,}{,}S{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}subtitle{;}text_1{,}Resumo{,}{,}5{,}{,}{,}html_light{,}{,}{,}{,}{,}{,}{,}{,}{,}summary{;}text_2{,}Texto{,}{,}20{,}{,}S{,}S{,}{,}{,}{,}{,}{,}{,}{,}{,}text{;}varchar_2{,}Créditos{,}{,}{,}{,}S{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}credits{;}file_1{,}Imagem{,}{,}{,}{,}S{,}0{,}S{,}{,}{,}{,}{,}{,}{,}{,}image{;}varchar_3{,}Link{,}{,}{,}{,}S{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}link{;}int_key{,}Ordem{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}ordem{;}char_key{,}Mostrar{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}mostrar{;}char_1{,}Destaque{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}featured{;}char_2{,}Destaque Home{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}home{;}',
        'children' => '',
        'files_ajuda' => '',
        'files' => '',
        'template' => 'news/index',
        'editpage' => '',
        'class' => '',
        'class_type' => '',
        'model_type_id' => 0,
        'table' => '',
        'layout' => Jp7_Box_Manager::COL_2_LEFT,
        'layout_registros' => Jp7_Box_Manager::COL_2_LEFT,
        'editar' => 1,
        'xtra_disabledfields' => 'array(\'char_2\')',
    ];

    public function __construct()
    {
        parent::__construct();
        if (!self::$_children) {
            $contentSubitem = $this->_findChildByModel('ContentSubitem');
            $images = $this->_findChildByModel('Images');
            $videos = $this->_findChildByModel('ContentVideos');
            $contentFiles = $this->_findChildByModel('ContentFiles');

            self::$_children = $contentSubitem->type_id . '{,}Subitens{,}{,}S{;}' .
                $images->type_id . '{,}Imagens{,}{,}S{;}' .
                $videos->type_id . '{,}Vídeos{,}{,}S{;}' .
                $contentFiles->type_id . '{,}Arquivos para Download{,}{,}S{;}';
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
            <?php echo parent::_getEditorImageFields($box, true, Jp7_Box_Manager::getRecordMode() ? 300 : 90, Jp7_Box_Manager::getRecordMode() ? 200 : 60);
            ?>
        </div>
<?php
        return ob_get_clean();
    }

    public function prepareData(Jp7_Box_BoxAbstract $box)
    {
        parent::_prepareImageData($box, Jp7_Box_Manager::getRecordMode() ? 300 : 90, Jp7_Box_Manager::getRecordMode() ? 200 : 60);

        $params = $box->params; // facilita
        $view = $box->view;

        $view->headStyle()->appendStyle('
.content-news div.subitem .image,
.content-news div.record .image {
	width: ' . $params->imgWidth . 'px;
}
');
    }
}
