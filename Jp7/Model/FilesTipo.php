<?php

class Jp7_Model_FilesTipo extends Jp7_Model_TipoAbstract
{
    public $attributes = [
        'type_id' => 'Files',
        'nome' => 'Arquivos para Download',
        'campos' => 'varchar_key{,}Nome{,}{,}{,}{,}S{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}name{;}file_1{,}Arquivo{,}{,}{,}S{,}S{,}trigger{,}S{,}{,}{,}{,}{,}{,}{,}{,}file{;}int_key{,}Ordem{,}{,}{,}{,}S{,}0{,}{,}1{,}{,}{,}{,}{,}{,}{,}ordem{;}char_key{,}Mostrar{,}{,}{,}{,}{,}S{,}{,}{,}{,}{,}{,}{,}{,}{,}mostrar{;}char_1{,}Destaque{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}featured{;}',
        'children' => '',
        'files_ajuda' => '',
        'files' => '',
        'template' => 'files/index',
        'editpage' => '',
        'class' => '',
        'class_type' => '',
        'model_type_id' => 0,
        'tabela' => '',
        'layout' => Jp7_Box_Manager::COL_2_LEFT,
        'layout_registros' => Jp7_Box_Manager::COL_2_LEFT,
        'editar' => 1,
        'icone' => 'page_white_put',
    ];

    public function createChildren(InterAdminTipo $type)
    {
        parent::createBoxesSettingsAndIntroduction($type);
    }

    public function getEditorFields(Jp7_Box_BoxAbstract $box)
    {
        ob_start();
        ?>
		<div class="fields">
			<?php echo parent::_getEditorImageFields($box);
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
