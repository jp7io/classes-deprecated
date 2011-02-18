<?php

class Jp7_Box_Facebook extends Jp7_Box_BoxAbstract {    /**
     * @see Jp7_Box_BoxAbstract::getEditorHtml()
     */
    public function getEditorHtml() {
    	$record = $this->record;
		?>
		<div class="box box-<?php echo $record->id_box; ?>">
			<?php echo ucwords(str_replace('-', ' ', $record->id_box)); ?>
			<div class="fields">
				<input type="hidden" name="box[]" value="<?php echo $record->id_box; ?>" />
				<label>Href:</label> <input type="text" class="textbox" value="<?php echo $record->params->href; ?>" name="<?php echo $record->id_box; ?>[href][]" /><br />
			</div>
		</div>
		<?php
    }
}