<?php
/**
 * JP7's PHP Functions 
 * 
 * Contains the main custom functions and classes.
 * @author JP7
 * @copyright Copyright 2002-2008 JP7 (http://jp7.com.br)
 * @category JP7
 * @package InterAdminArquivo
 */
 
/**
 * Class which represents records on the table interadmin_{client name}_arquivos.
 *
 * @package InterAdminArquivo
 */
class InterAdminArquivo extends InterAdminAbstract {
	
	protected $_primary_key = 'id_arquivo';
	
	/**
	 * Table prefix of this record. It is usually formed by 'interadmin_' + 'client name'.
	 * @var string
	 */
	public $db_prefix;
	/**
	 * Contains the InterAdminTipo, i.e. the record with an 'id_tipo' equal to this record´s 'id_tipo'.
	 * @var InterAdminTipo
	 */
	protected $_tipo;
	/**
	 * Contains the parent InterAdmin object, i.e. the record with an 'id' equal to this record's 'parent_id'.
	 * @var InterAdmin
	 */
	protected $_parent;
	/**
	 * Public Constructor. If $options['fields'] was passed the method $this->getFieldsValues() is called.
	 * 
	 * @param int $id_arquivo This record's 'id_arquivo'.
	 * @param array $options Default array of options. Available keys: db_prefix, fields.
	 */
	public function __construct($id_arquivo = 0, $options = array()) {
		$this->id_arquivo = $id_arquivo;
		$this->db_prefix = ($options['db_prefix']) ? $options['db_prefix'] : $GLOBALS['db_prefix'];
		if ($options['fields']) {
			$this->getFieldsValues($options['fields']);
		}
	}
	/**
	 * Gets the InterAdminTipo object for this record, which is then cached on the $_tipo property.
	 * 
	 * @param array $options Default array of options. Available keys: class.
	 * @return InterAdminTipo
	 */
	public function getTipo($options = array()) {
		if (!$this->_tipo) {
			if (!$this->id_tipo) {
				$this->id_tipo = jp7_fields_values($this->db_prefix . $this->table, 'id', $this->id, 'id_tipo');
			}
			$this->_tipo = InterAdminTipo::getInstance($this->id_tipo, array(
				'db_prefix' => $this->db_prefix,
				'class' => $options['class']
			));
		}
		return $this->_tipo;
	}
	/**
	 * Sets the InterAdminTipo object for this record, changing the $_tipo property.
	 *
	 * @param InterAdminTipo $tipo
	 * @return void
	 */
	public function setTipo($tipo) {
		$this->id_tipo = $tipo->id_tipo;
		$this->_tipo = $tipo;
	}
	/**
	 * Gets the parent InterAdmin object for this record, which is then cached on the $_parent property.
	 * 
	 * @param array $options Default array of options. Available keys: db_prefix, table, fields, fields_alias, class.
	 * @return InterAdmin
	 */
	public function getParent($options = array()) {
		if ($this->_parent) return $this->_parent;
		if ($this->id || $this->getFieldsValues('id')) {
			return $this->_parent = InterAdmin::getInstance($this->id, $options);
		}
	}
	/**
	 * Sets the parent InterAdmin object for this record, changing the $_parent property.
	 *
	 * @param InterAdmin $parent
	 * @return void
	 */
	public function setParent($parent) {
		$this->id = $parent->id;
		$this->_parent = $parent;
	}
	/**
	 * Returns the full url address of this file.
	 *
	 * @return string
	 */
	public function getUrl(){
		global $c_url;
		$url = ($this->url) ? $this->url : $this->getFieldsValues('url');
		$url = str_replace('../../', $c_url, $url);
		return $url; 
	}
	
    function getAttributesAliases() {
       return array();
    }
    function getAttributesCampos() {
		return array();
    }
    function getAttributesNames() {
		return array('id_arquivo', 'id_tipo', 'id', 'parte', 'url', 'url_thumb', 'url_zoom', 'url_mac', 'nome', 'legenda', 'creditos', 'link', 'link_blank', 'mostrar', 'destaque', 'ordem', 'deleted');
    }
	function getTableName() {
    	if ($this->id_tipo) {
			return $this->getTipo()->getArquivosTableName();
		} else {
			return $this->db_prefix . '_arquivos';
		}
    }
}
