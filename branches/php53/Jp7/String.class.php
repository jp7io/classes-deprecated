<?php
namespace Jp7;

class String {
	private $str;
	
	function __construct($str) {
		$this->str = $str;
	}
	
	public function startsWith($str) {
		return startsWith($str, $this->str);
	}
	
	public function endsWith($str) {
		return endsWith($str, $this->str);
	}
	
	public function sub($start, $end) {
		return substr($this->str, $start, $end);
	}
	
	public function length() {
		return strlen($this->str);
	}
	
	public function __toString() {
		return $this->str;
	}
}