<?php

abstract class Jp7_Box_BoxAbstract {
	public $record;
	
	public function __construct(InterAdmin $record = null) {
		$this->record = $record;
		if ($this->record->params) {
			$this->record->params = unserialize($this->record->params);
		}
	}
	/**
	 * Prepara os dados que vão ser utilizados na view do box mais tarde. 
	 * Exemplo: Faz a busca das notícias que vão ser exibidas.
	 * 
	 * @return void
	 */
	public function prepareData() {
		// Vazio por padrão
	}
	
	public function getEditorHtml() {
		?>
		<div class="box box-<?php echo $this->record->id_box; ?>">
			<?php echo ucwords(str_replace('-', ' ', $this->record->id_box)); ?>
			<input type="hidden" name="box[]" value="<?php echo $this->record->id_box; ?>" />
		</div>
		<?php
	}
}