<?php

/**
 * Necessário para funcionar com use => literal e style => document.
 */
class Jp7_InterAdmin_Soap_LiteralDocumentProxy {
	public function __call($methodName, $args) {
		$soapClass = new Jp7_InterAdmin_Soap();
		$result = call_user_func_array(array($soapClass, $methodName),  $args[0]);
		return array($methodName . 'Result' => $result);
	}
}