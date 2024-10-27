<?php

class Jp7_Box_Sections extends Jp7_Box_BoxAbstract
{
    public function prepareData()
    {
        if ($section = $this->params->section) {
            if ($this->sectionTipo = InterAdminTipo::getInstance($section)) {
                $this->title = ($this->params->title) ? $this->params->title : $this->sectionTipo->getNome();

                $options = [
                    'fields' => ['nome'],
                    'where' => ["menu <> ''"],
                    'limit' => $this->params->limit,
                ];

                $this->sections = $this->sectionTipo->getChildren($options);
            }
        }
    }

    /**
     * @see Jp7_Box_BoxAbstract::_getEditorTitle()
     */
    protected function _getEditorTitle()
    {
        return 'Seções';
    }

    /**
     * @see Jp7_Box_BoxAbstract::_getEditorFields()
     */
    protected function _getEditorFields()
    {
        ob_start();
        ?>
		<div class="fields">
			<div class="field">
				<label>Título:</label>
				<input type="text" class="textbox" label="Título" placeholder="Automático"
					name="<?php echo $this->id_box;
        ?>[title][]"
					value="<?php echo $this->params->title;
        ?>"	/>
			</div>
			<div class="field obligatory">
				<label>Seção Pai:</label>
				<select class="selectbox" obligatory="yes" label="Seção" name="<?php echo $this->id_box;
        ?>[section][]">
					<?php
                    $tipos = InterAdminTipo::findTipos([
                        'where' => [
                            "admin = ''",
                            "menu != ''",
                            // "model_type_id NOT IN ('Boxes', 'Settings', 'Introduction', 'Images')"
                        ],
                        'order' => 'parent_type_id, ordem',
                        'use_published_filters' => true,
                    ]);
        ?>
					<?php echo $this->tiposOptions($tipos,  $this->params->section);
        ?>
				</select>
			</div>
			<div class="field">
				<label>Limite:</label>
				<?php echo $this->numericField('limit', 'Limite', 'Todos');
        ?>
			</div>
		</div>
		<?php
        return ob_get_clean();
    }
}
