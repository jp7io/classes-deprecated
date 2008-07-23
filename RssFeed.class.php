<?php
/**
 * class RssFeed
 * 
 * @package RssFeed
 * @author Carlos Rodrigues
 * @version (2008/07/11)
 * @todo Add CDATA functionality, e.g. <description><![CDATA[this is <b>bold</b>]]></description>  
 */
class RssFeed extends DOMDocument {
	/*
	 * @var string The name of the channel.
	 */
 	public $title;
	/*
	 * @var string The URL to the HTML website corresponding to the channel.
	 */
	public $link;
	/*
	 * @var string Phrase or sentence describing the channel.
	 */
	public $description;
	/*
	 * @var array Correspondences between the column and the xml tag names.
	 */
	public $rssRowItens = array('title' => 'varchar_key',
			'link' => '',
			'description' => 'text_1',
			'author' => '',
			'category' => '',
			'comments' => '',
			'pubDate' => 'date_publish',
			'guid' => '');
	/**
	 * @var array Configuration of channel properties.
	 */
	public $rssChannel = array(
			'docs' => 'http://blogs.law.harvard.edu/tech/rss',
			'generator' => 'JP7 InterAdmin',
			'managingEditor' => '',
			'webMaster' => 'debug@jp7.com.br');
	/**
	 * Creates a RssFeed object.
	 * 
	 * @param string The version number of the document as part of the XML declaration. 
	 * @param string The encoding of the document as part of the XML declaration. 
	 * @return RssFeed
	 * @version (2008/06/27)
	 */
	function __construct($version = '1.0', $encoding = 'ISO-8859-1') {
		parent::__construct($version, $encoding);
	}
	/**
	 * Creates a RSS feed from an array of recordsets.
	 *
	 * @param array Recordsets with columns matching the itens on rssRowItens.
	 * @param string RSS version of the document.
 	 * @return string RSS Document.
	 * @version (2008/07/11)
	 */
	function parseArray($rows, $rssVersion = '2.0') {
		global $lang;
		
		$this->formatOutput = TRUE;
				
		$rss = $this->appendChild($this->createElement('rss'));
		$rss->setAttribute('version', $rssVersion);
		
		$channel = $rss->appendChild($this->createElement('channel'));
		
		$channel->appendChild($this->createElement('title', $this->title));
		$channel->appendChild($this->createElement('link', $this->link));
		$channel->appendChild($this->createElement('description', $this->description));
		$channel->appendChild($this->createElement('lang', $lang->lang));
		$channel->appendChild($this->createElement('pubDate', date('r')));
		$channel->appendChild($this->createElement('lastBuildDate', date('r')));
				
		foreach ($this->rssChannel as $child=>$value) {
			$channel->appendChild($this->createElement($child, $value));
		}
		foreach((array)$rows as $row) {
			$row = (array) $row;
			$item = $channel->appendChild($this->createElement('item'));
			foreach($this->rssRowItens as $rssitem=>$rowitem) {
				$item->appendChild($this->createElement($rssitem, $this->xmlEntities($row[$rowitem])));
			}
		}
		header( "content-type: application/xml; charset=" . $this->encoding );
		return $this->saveXML();
	}
	/**
	 * Converts special characters to NCR(Numeric Character Reference) since it is not handled by DOMDocument.
	 *
	 * @param string Input string.
 	 * @return string String with NCRs replacing special characters.
	 * @version (2008/07/11)
	 */
	function xmlEntities($str){
		$str = str_replace('&', '&amp;', $str);
		foreach (get_html_translation_table(HTML_ENTITIES, ENT_QUOTES) as $k => $v) {
			if ($k == '&') continue;
			$str = str_replace($k, "&#" . ord($k) . ";", $str);
		}
		return $str;
	}
}