<? 
/**
 * Class which represents a site on InterSite.
 * 
 * @author Carlos Rodrigues
 * @copyright Copyright 2002-2008 JP7 (http://jp7.com.br)
 * @version (2008/07/30)
 * @package InterSite
 */
 
/**
 * class InterSite
 *
 * @package InterSite
 */
class InterSite extends InterAdmin {
	function getServers() {
		$options = array(
			'fields' => array('varchar_key', 'select_1', 'varchar_1', 'varchar_2',  'varchar_3', 'password_key', 'varchar_4', 'select_2'),
			'fields_alias' => TRUE
		);
		$servers = $this->getChildren(26, $options);
		
		// Variaveis do site
		$vars = $this->getChildren(39, array( 'fields' => array( 'select_key', 'varchar_1')));
		
		foreach ((array) $servers as $server) {
			$server->vars = NULL;
			// Variaveis do server
			$server_vars = $server->getChildren(39, array( 'fields' => array( 'select_key', 'varchar_1')));
			foreach((array) $vars as $var) {
				$varName = new InterAdmin($var->select_key, array('fields' => 'varchar_1'));
				$varName = $varName->varchar_1;
				$server->vars->$varName = $var->varchar_1;
			}
			foreach((array) $server_vars as $var) {
				$varName = new InterAdmin($var->select_key, array('fields' => 'varchar_1'));
				$varName = $varName->varchar_1;
				$server->vars->$varName = $var->varchar_1;
			}
			// Tipo 
			$type = new InterAdmin($server->type, array('fields' => 'varchar_key'));
			$server->type = $type->varchar_key;
			// Database
			$options = array(
				'fields' => array('varchar_key', 'varchar_1', 'varchar_2', 'varchar_3', 'varchar_4', 'password_key', 'select_2'),
				'fields_alias' => TRUE
			);
			$server->db = new InterAdmin($server->db, $options);
			$type = new InterAdmin($server->db->type, array('fields' => 'varchar_1'));
			$server->db->type = $type->varchar_1;
			// Aliases
			$aliasesObj = $server->getChildren(31, array('fields' => 'varchar_key'));
			$server->aliases = NULL;
			foreach ((array)$aliasesObj as $aliasObj) {
				$server->aliases[] = $aliasObj->varchar_key;
			}
			// Cleaning unused data
			unset($server->db->db_prefix);
			unset($server->db->tipo);
			unset($server->db_prefix);
			unset($server->tipo);
		}
		// Renaming keys
		foreach ($servers as $server) {
			$renamed_servers[$server->host] = $server;
		}
		$this->servers = $renamed_servers;
		return $this->servers;
	}
	function __wakeup() {
		// This server is a main host
		$this->server = $this->servers[$_SERVER['HTTP_HOST']];
		if (!$this->server) {
			// This server is not there, it might be an alias
			foreach ($this->servers as $host=>$server) {
				if (in_array($_SERVER['HTTP_HOST'], (array) $server->aliases)) {
					// Alias found, redirect it to the host
					header('Location: http://' . $host . $_SERVER['REQUEST_URI']);
					exit();
				}
			}
			// No alias found, die
			die(jp7_debug('Host não está presente nas configurações.'));
		}
	}
}
?>