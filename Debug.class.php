<?
/**
 * Debug class, used to display filenames, processing time and display formatted SQL queries.
 * 
 * @author Carlos Rodrigues
 * @copyright Copyright 2002-2008 JP7 (http://jp7.com.br)
 * @version 1.02 (2008/07/18)
 * @package Debug
 * @todo Documentation, and showToolbar
 */
 
/**
 * class Debug
 *
 * @package Debug
 */
class Debug{
	/**
	 * @var bool Flag, it is <tt>TRUE</tt> if its displaying filenames or SQL queries.
	 */
	public $active;
	/**
	 * @var bool Flag indicating if filenames will be showed or not. Use $_GET['debug_filename'] to set it.
	 */
	public $debugFilename;
	/**
	 * @var bool Flag indicating if the SQL queries will be showed or not. Use $_GET['debug_sql'] to set it.
	 */
	public $debugSql;
	/**
	 * @var array Array containing the activity log for queries, filenames and their processing time.
	 */
	protected $log;
	/**
	 * @var bool In order to prevent errors with output and headers, set this variable <tt>TRUE</tt> after the headers are sent.
	 */
	public $safePoint;
	/**
	 * @var float|array Stores the start time which is used to calculate the processing time. Use the method startTime() to set this variable.
	 */
	protected $startTime;
	/**
	 * @var bool Indicates the current template file loaded. Used on the showToolbar() method.
	 */
	protected $templateFilename;
	/**
	 * Constructor function, initial checks and settings when object is created.
	 *
	 * @global bool
	 * @return Debug
	 */	
	public function __construct() {
		global $c_jp7;
		if (!$c_jp7) return; // Only by Devs
		$this->startTime();
		// Debug - SQL
		$this->debugSql = $_GET['debug_sql'];
		// Debug - Filename
		if (isset($_GET['debug_filename'])) {
			setcookie('debug_filename', $_GET['debug_filename'], 0, '/');
			$_COOKIE['debug_filename'] = $_GET['debug_filename'];
		}
		if ($_COOKIE['debug_filename']) $this->debugFilename = $_COOKIE['debug_filename'];
		// Debug - Toolbar
		if (isset($_GET['debug_toolbar'])){
			setcookie('debug_toolbar', $_GET['debug_toolbar'], 0, '/');
			$_COOKIE['debug_toolbar'] = $_GET['debug_toolbar'];
		}
		// Setting it as active
		if ($_COOKIE['debug_toolbar'] || $this->debugSql || $this->debugFilename) $this->active = TRUE;
	}
	/**
	 * Starts recording the time spent on the code. When using more than one startTime(), the time will be displayed from the last to the first when getTime() is called.
	 */	
	public function startTime() {
		$debug_mtime = explode(' ', microtime());
		$this->startTime[] = $debug_mtime[1] + $debug_mtime[0];
	}
	/**
	 * Calculates and displays the time spent from the moment startTime() was called.
	 */	
	public function getTime($output = FALSE) {
		if (!count($this->startTime)) return FALSE;
		$debug_mtime = explode(' ', microtime());
		// Retrieves and deletes the last value
		$debug_starttime = array_pop($this->startTime);
		$debug_totaltime = round(($debug_mtime[0] + $debug_mtime[1] - $debug_starttime) * 1000);
		if ($output && $this->safePoint) echo '<div class="debug_msg">Processed in: ' . $debug_totaltime . 'ms.</div>';
		return $debug_totaltime;
	}
	/**
	 * Shows the filename. Do not shows paths containing 'inc/connection', 'inc/7.' or 'classes/'.
	 *
	 * @param string $filename Name of the file.
	 * @global string
	 */	
	public function showFilename($filename) {
		global $c_doc_root;
		if ($this->debugFilename && $this->safePoint) echo '<div class="debug_msg">' .  str_replace($c_doc_root, '/', $filename ) . '</div>';
		if ($this->active) {
			// Creates a new log entry for this file
			$this->addLog($filename, 'file');
		}
		return $filename;
	}
	/**
	 * Formats and displays an SQL query.
	 *
	 * @param string $sql SQL query to be formatted and displayed.
	 * @param bool $forcedebug If <tt>TRUE</tt> it will show the SQL even when $_GET['debug_sql'] is not set, the default value is <tt>FALSE</tt>.
	 * @param string Stylesheet on the box displayed. The default value is ''.
	 */	
	public function showSql($sql, $forcedebug = FALSE, $style = '') {
		if (!$this->safePoint) return FALSE;
		if ($this->debugSql || $forcedebug) echo '<div class="debug_sql" style="' . $style . '">' . preg_replace(array('/(SELECT )/','/( FROM )/','/( WHERE )/','/( ORDER BY )/'),'<b>\1</b>', $sql, 1) . '</div>';
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
	
	public function errorHandler($code, $msgErro) {
			if ($code == E_STRICT || $code == E_NOTICE || $code == E_DEPRECATED) return FALSE; // FALSE -> the default error handler will take care of it.
			if (error_reporting() == 0) return FALSE; // Programmer used @ so the error reporting value is 0.

			$backtrace = debug_backtrace();
			array_shift($backtrace);
			die(jp7_debug($msgErro, NULL, $backtrace));
	}
	
	public function addLog($value, $tag = 'log', $time = NULL) {
		$this->log[] = array('tag' => $tag, 'value' => $value, 'time' => $time);
	} 
	public function getLog() {
		return $this->log;
	} 
	public function setTemplateFilename($filename) {
		$this->templateFilename = $filename;
	}
	public function getTemplateFilename() {
		return $this->templateFilename;
	}
	public function showToolbar() {
		if (!$this->active || !$this->safePoint) return FALSE;
		
		if ($this->templateFilename ) echo ('Template: ' . $this->templateFilename );
		else echo('PHP_SELF: ' . $_SERVER['PHP_SELF']);
		
		jp7_print_r($this->log);
		$this->getTime(TRUE);
	}
}
?>