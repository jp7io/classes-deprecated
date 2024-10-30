<?php

class Jp7_Model_VideosTipo extends Jp7_Model_TipoAbstract
{
    public $attributes = [
        'type_id' => 'Videos',
        'nome' => 'Vídeos',
        'campos' => 'varchar_key{,}Título{,}{,}{,}{,}S{,}0{,}{,}2{,}{,}{,}{,}{,}{,}{,}title{;}varchar_1{,}Vídeo{,}Endereço do vídeo no YouTube ou Vimeo. Ex: http://www.youtube.com/watch?v=123ab456{,}{,}S{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}video{;}file_1{,}Thumb{,}Caso não seja cadastrada, será usada a imagem do YouTube para preview do vídeo.{,}{,}{,}{,}0{,}S{,}{,}{,}{,}{,}{,}{,}{,}thumb{;}varchar_2{,}Duração{,}{,}{,}{,}S{,}0{,}S{,}{,}{,}{,}{,}{,}{,}{,}duration{;}text_1{,}Descrição{,}{,}5{,}{,}S{,}S{,}{,}{,}{,}{,}{,}{,}{,}{,}summary{;}int_key{,}Ordem{,}{,}{,}{,}S{,}0{,}{,}1{,}{,}{,}{,}{,}{,}{,}ordem{;}char_key{,}Mostrar{,}{,}{,}{,}{,}S{,}{,}{,}{,}{,}{,}{,}{,}{,}mostrar{;}char_1{,}Destaque{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}featured{;}',
        'children' => '',
        'files_ajuda' => '',
        'files' => '',
        'template' => 'videos/index',
        'editpage' => '',
        'class' => '',
        'class_type' => '',
        'model_type_id' => 0,
        'tabela' => '',
        'layout' => Jp7_Box_Manager::COL_2_LEFT,
        'layout_registros' => Jp7_Box_Manager::COL_2_LEFT,
        'editar' => 1,
        'texto' => 'Cadastro de vídeos do YouTube e Vimeo.',
        'disparo' => 'Jp7_Model_VideosTipo::checkThumb',
        'icone' => 'film',
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
			<?php if (Jp7_Box_Manager::getRecordMode()) {
    ?>
				<div class="group">
					<div class="group-label">Vídeo</div>
					<div class="group-fields">
						<div class="field">
							<label>Dimensões:</label>
							<?php echo $box->numericField('videoWidth', 'Largura', '620');
    ?> x
							<?php echo $box->numericField('videoHeight', 'Altura', '380');
    ?> px
						</div>
					</div>
				</div>
			<?php
} else {
    ?>
				<?php echo parent::_getEditorImageFields($box, false, 310, 230);
    ?>
			<?php
}
        ?>
		</div>
		<?php
        return ob_get_clean();
    }

    public function prepareData(Jp7_Box_BoxAbstract $box)
    {
        if (Jp7_Box_Manager::getRecordMode()) {
            $box->params = (object) $box->params;

            $box->params->videoWidth = $box->params->videoWidth ? $box->params->videoWidth : 620;
            $box->params->videoHeight = $box->params->videoHeight ? $box->params->videoHeight : 380;
            $box->view->params = $box->params;
        } else {
            parent::_prepareImageData($box, 310, 230);
        }
    }

    public static function checkThumb($from, $id, $type_id)
    {
        global $interadminObj;

        if ($from == 'edit' || $from == 'insert') {
            $type = InterAdminTipo::getInstance($type_id);
            $registro = $type->findById($id, [
                'fields' => ['video', 'thumb', 'title', 'duration'],
            ]);

            if ($registro->video) {
                if (!$registro->title) {
                    self::_updateTitle($registro);
                }
                if ($registro->video != $interadminObj->varchar_1 || !$registro->thumb) {
                    self::_updateThumb($registro);
                }
                if ($registro->video != $interadminObj->varchar_1 || !$registro->duration) {
                    self::_updateDuration($registro);
                }
            }
        }
    }

    protected static function _updateThumb($registro)
    {
        // Salvando thumb caso esteja vazio e seja um vídeo do YouTube ou Vimeo
        if (Jp7_YouTube::matchUrl($registro->video)) {
            $registro->updateAttributes([
                'thumb' => Jp7_YouTube::getThumbnail($registro->video),
            ]);
        } elseif (str_starts_with($registro->video, 'http://vimeo.com')) {
            $registro->updateAttributes([
                'thumb' => Jp7_Vimeo::getThumbnailLarge($registro->video),
            ]);
        }
    }

    protected static function _updateTitle($registro)
    {
        // Salvando thumb caso esteja vazio e seja um vídeo do YouTube ou Vimeo
        if (Jp7_YouTube::matchUrl($registro->video)) {
            $registro->updateAttributes([
                'title' => Jp7_YouTube::getTitle($registro->video),
            ]);
        }
    }

    protected static function _updateDuration($registro)
    {
        // Salvando thumb caso esteja vazio e seja um vídeo do YouTube ou Vimeo
        if (Jp7_YouTube::matchUrl($registro->video)) {
            $registro->updateAttributes([
                'duration' => Jp7_YouTube::getDuration($registro->video),
            ]);
        }
    }
}
