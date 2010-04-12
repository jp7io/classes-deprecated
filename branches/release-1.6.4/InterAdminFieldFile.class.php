<?php
/**
 * JP7's PHP Functions 
 * 
 * Contains the main custom functions and classes.
 * @author JP7
 * @copyright Copyright 2002-2008 JP7 (http://jp7.com.br)
 * @category JP7
 * @package InterAdmin
 */

/**
 * Handles the url of uploaded files retrieved from the database.
 * 
 * @package InterAdmin
 */
class InterAdminFieldFile {
	protected $_parent;
	/**
	 * Créditos/Legenda da imagem.
	 * @var
	 */
	public $text;
	/**
	 * Url da imagem.
	 * @var
	 */
	public $url;
	
	public function __construct($url, $text = '') {
		$this->url = $url;
		$this->text = $text;
	}
	public function __toString() {
		return $this->url;
	}
	public function getUrl() {
		return $this->url;
	}
	public function getText() {
		return $this->text;
	}
	/**
     * Returns $parent.
     *
     * @see InterAdminFile::$parent
     */
    public function getParent() {
        return $this->_parent;
    }
    /**
     * Sets $parent.
     *
     * @param object $parent
     * @see InterAdminFile::$parent
     */
    public function setParent($parent) {
        $this->_parent = $parent;
    }
}