<? 
/**
 * class Browser
 *
 * @version (2005/11/18)
 * @package Browser
 */
class Browser{
	/**
	 * Checks browser, browser version, and whether it's a robot or not.
	 *
	 * @param string $useragent Browser information from $HTTP_USER_AGENT.
	 * @return Browser
	 */	
	function Browser($useragent){
		$this->userAgent = $useragent;
		$i = 0;
		if (strpos($useragent, 'Safari')) {
			$this->browser = 'sa';
			$this->v = 5;
		} elseif (strpos($useragent, 'Opera')) {
			$this->browser = 'op';
			$i = strpos($useragent, 'Opera') + 6;
		} elseif (strpos($useragent, 'MSIE')) {
			$this->browser = 'ie';
			$i = strpos($useragent, 'MSIE') + 4;
		} elseif (strpos($useragent, 'Mozilla/') !== FALSE && strpos($useragent, 'compatible') === FALSE) {
			$this->browser = 'ns';
			$i = strpos($useragent, 'Mozilla/') + 8;
		} elseif(strpos($useragent, 'Mozilla/5.0') !== FALSE) {
			$this->browser = 'mo';
			$this->v = 5;
		} else {
			$this->browser = $useragent;
			$this->v = -1;
		}
		$this->sa = ($this->browser == 'sa');
		$this->op = ($this->browser == 'op');
		$this->ie = ($this->browser == 'ie');
		$this->ns = ($this->browser == 'ns');
		$this->mo = ($this->browser == 'mo');
		$version = '';
		while (!$this->v) {
			$c = substr($useragent, $i++, 1);
			if (is_numeric($c) || $c == '.'|| $c == ' ') $version .= "$c";
			else $this->v = ($version) ? doubleval($version) : -1;
		}
		$this->ns4 = ($this->ns && $version < 5);
		if (strpos($useragent, 'Win')) $this->os = 'win';
		elseif (strpos($useragent, 'Mac')) $this->os = 'mac';
		elseif (strpos($useragent, 'Unix')) $this->os = 'unx';
		elseif (strpos($useragent, 'Linux')) $this->os = 'lnx';
		elseif (strpos($useragent, 'SunOS')) $this->os = 'sol';
		else $this->os = NULL;
		$this->win = ($this->os == 'win');
		$this->mac = ($this->os == 'mac');
		$this->unx = ($this->os == 'unx');
		$this->lnx = ($this->os == 'lnx');
		$this->sol = ($this->os == 'sol');
		// Robots	
		if ($this->browser == $useragent) {
			$robots = array(
				"wget",
				"getright",
				"yahoo",
				"altavista",
				"lycos",
				"infoseek",
				"lwp",
				"webcrawler",
				"linkexchange",
				"slurp",
				"google"
			);
			for ($i = 0; $i < count($robots); $i++) {
				if (strpos(strtolower($useragent), $robots[$i]) !== FALSE) {
					$this->robot = $robots[$i];
					$this->browser = 'robot';
					break;
				}
			}
		}
	}
} 
?>