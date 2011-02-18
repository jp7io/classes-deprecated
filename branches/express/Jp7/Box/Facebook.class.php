<?php

class Jp7_Box_Facebook extends Jp7_Box_BoxAbstract {    /**
     * @see Jp7_Box_BoxAbstract::_getEditorFields()
     */
    protected function _getEditorFields() {
    	ob_start();
		?>
		<div class="fields">
			<label>Href:</label>
			<input type="text" class="textbox" value="<?php echo $this->record->params->href; ?>" name="<?php echo $this->record->id_box; ?>[href][]" />
		</div>
		<?php
		return ob_get_clean();
    }
}