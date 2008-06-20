<?
/**
 * Debug class, used to display filenames, processing time and display formatted SQL queries.
 * 
 * @author Carlos
 * @copyright Copyright 2002-2008 JP7 (http://jp7.com.br)
 * @version 1.01 (2008/06/16)
 * @package Debug
 */
 
/**
 * class Debug
 *
 * @package Debug
 */
class Debug{
	/**
	 * @var float Stores the start time which is used to calculate the processing time. Using the method startTime() you will be setting this variable.
	 */
	protected $startTime;
	/**
	 * @var bool Flag indicating if filenames will be debugged or not. Use $_GET['debug_time'] or $_GET['cookie_debug_filename'] to set it.
	 */
	protected $debugFilename;
	/**
	 * @var bool|string Flag indicating if the SQL queries will be debugged or not. Use $_GET['debug_sql'] to set it.
	 */
	protected $debugSql;
	/**
	 * @var string CSS stylesheet used to display the filename and time debugging. Can be changed directly.
	 */
	public $debugStyle;
	/**
	 * @var bool In order to prevent errors when output is sent before session is started, set this variable <tt>TRUE</tt> after the headers are sent.
	 */
	public $safePoint;
	/**
	 * Constructor function, initial checks and settings when object is created.
	 *
	 * @global bool
	 * @return Debug
	 */	
	public function __construct() {
		global $c_jp7;
		if ($c_jp7){ // Only by Devs
			// Debug - SQL
			$this->debugSql = $_GET['debug_sql'];
			// Debug - processing time
			if (isset($_GET['debug_time'])){
				setcookie('debug_time', $_GET['debug_time'], 0, '/');
				$_COOKIE['debug_time'] = $_GET['debug_time'];
			}
			if ($_COOKIE['debug_time']) $this->startTime();
			// Debug - filename
			if (isset($_GET['cookie_debug_filename'])){
				setcookie('cookie_debug_filename', $_GET['cookie_debug_filename'], 0, '/');
				$_COOKIE['cookie_debug_filename'] = $_GET['cookie_debug_filename'];
			}
			$this->debugFilename = ($_GET['debug_filename']) ? $_GET['debug_filename'] : $_COOKIE['cookie_debug_filename'];
			// CSS Stylesheet to display the divs
			if ($this->debugFilename) $this->debugStyle = '<style>div.filename{display:block}</style>';
		}
	}
	/**
	 * Starts 'recording' the time spent on the code.
	 */	
	public function startTime() {
			$this->startTime = explode(' ', microtime());
			$this->startTime = $this->startTime[1] + $this->startTime[0];
			if (!$this->debugStyle) $this->debugStyle = '<style>div.filename{display:block}</style>';
	}
	/**
	 * Calculates and displays the time spent from the moment you called startTime().
	 */	
	public function showTime() {
		if ($this->startTime){
			$debug_mtime = explode(' ', microtime());
			$debug_totaltime = $debug_mtime[0] + $debug_mtime[1] - $this->startTime;
			printf('<div class="filename">Page created in:  %.3f seconds.</div>', $debug_totaltime);
		}
	}
	/**
	 * Shows the current filename using the PHP_SELF or shows the template filename if it is set.
	 *
	 * @param string $template_filename Name of the template used, if its passed the PHP_SELF will not be displayed.
	 */	
	public function showSelfFilename($template_filename = '') {
		if ($this->debugFilename) {
			if ($template_filename) $this->showFilename('template_filename: ' . $template_filename);
			else $this->showFilename('PHP_SELF: ' . $_SERVER['PHP_SELF']);
		}
	}
	/**
	 * Shows the filename. Do not shows paths containing 'inc/connection', 'inc/7.' or 'classes/'.
	 *
	 * @param string $filename Name of the file.
	 * @global string
	 */	
	public function showFilename($filename) {
		if ($this->debugFilename) {
			global $c_doc_root;
			$ignore = array('inc/connection', 'inc/7.', 'classes/');
			foreach ($ignore as $value) if (strpos($filename, $value) !== FALSE) return $filename;
			echo '<div class="filename">' .  str_replace($c_doc_root, '/', $filename ) . '</div>';
		}
		return $filename;
	}
	/**
	 * Formats and displays an SQL query. If $_GET['debug_sql'] = 'all' it will display the SQL even when $safePoint is not set.
	 *
	 * @param string $sql SQL query to be formatted and displayed.
	 * @param bool $forcedebug If <tt>TRUE</tt> it will show the SQL even when $_GET['debug_sql'] is not set, the default value is <tt>FALSE</tt>.
	 * @param string Color of the font on the box displayed. The default value is '#FFFFFF'.
 	 * @param string Color of the background on the box displayed. The default value is '#008888'.
	 */	
	public function showSql($sql, $forcedebug = FALSE, $color='#FFFFFF', $bgcolor='#008888') {
		if ($this->debugSql || $forcedebug){
			if ($this->debugSql != 'all' && !$this->safePoint) return FALSE;
			echo '<div style="width:auto;color:' . $color . ';background:' . $bgcolor . ';padding:3px;font-weight:normal;text-align:left">' . preg_replace(array('/(SELECT )/','/( FROM )/','/( WHERE )/','/( ORDER BY )/'),'<b>\1</b>', $sql, 1) . '</div>';
		}
	}
	/**
	 * Formats and returns the backtrace.
	 *
	 * @param string $msgErro Error message (optional).
	 * @param string $sql SQL query which was executed (optional).
 	 * @return string Formatted backtrace.
	 */	
	public function getBacktrace($msgErro = NULL, $sql = NULL, $backtrace = NULL) {
		if (!$backtrace) $backtrace = debug_backtrace();
		krsort($backtrace);
		$erroDetalhesArray = reset($backtrace);
		$S = '';
		if ($msgErro) $S .= '<strong style="color:red">       ERRO:</strong> ' . wordwrap($msgErro, 85, "\n")  . "\n";
		$S .= '<strong style="color:red">    ARQUIVO:</strong> ' . $erroDetalhesArray['file'] . "\n";	
		$S .= '<strong style="color:red">      LINHA:</strong> ' . $erroDetalhesArray['line'] . "\n";	
		$S .= '<strong style="color:red">        URL:</strong> ' . (($_SERVER['HTTPS'] == 'on') ? "https://" : "http://") . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"] . "\n";
		if ($_SERVER["HTTP_REFERER"]) $S .= '<strong style="color:red">    REFERER:</strong> ' . $_SERVER["HTTP_REFERER"] . "\n";	
		$S .= '<strong style="color:red">         IP:</strong> ' . $_SERVER["REMOTE_ADDR"] . "\n";
		$S .= '<strong style="color:red"> USER_AGENT:</strong> ' . $_SERVER['HTTP_USER_AGENT'] . "\n";
		if ($sql) $S .= '<strong style="color:red">        SQL:</strong> ' . preg_replace(array('/( FROM )/','/( WHERE )/','/( ORDER BY )/'), "\n" . '            \1', $sql)  . "\n";
		$S .= '<strong style="color:red">  BACKTRACE:</strong> ' . preg_replace(array('/( FROM )/','/( WHERE )/','/( ORDER BY )/'), "\n" . '                          \1', print_r($backtrace, TRUE));
		if (count($_POST)) $S .= '<strong style="color:red">       POST:</strong> ' . print_r($_POST, TRUE);	
		if (count($_GET)) $S .= '<strong style="color:red">        GET:</strong> ' . print_r($_GET, TRUE);
		if (count($_SESSION)) $S .= '<strong style="color:red">    SESSION:</strong> ' . print_r($_SESSION, TRUE);
		if (count($_COOKIE)) $S .= '<strong style="color:red">     COOKIE:</strong> ' . print_r($_COOKIE, TRUE);
		return '<pre style="background-color:#FFFFFF;font-size:11px;text-align:left;">' . $S . '</pre>';
	}
}


?>