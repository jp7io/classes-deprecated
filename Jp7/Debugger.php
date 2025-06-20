<?php

/**
 * JP7's PHP Functions.
 *
 * Contains the main custom functions and classes.
 *
 * @author JP7
 * @copyright Copyright 2002-2008 JP7 (http://jp7.com.br)
 *
 * @category Jp7
 */

/**
 * Debug class, used to display filenames, processing time and display formatted SQL queries.
 */
class Jp7_Debugger
{
    const EMAIL = 'debug@jp7.com.br';
    /**
     * Flag indicating if filenames will be showed or not. Use $_GET['debug_filename'] to set it.
     *
     * @var bool
     */
    public $debugFilename;
    /**
     * Flag indicating if the SQL queries will be showed or not. Use $_GET['debug_sql'] to set it.
     *
     * @var bool
     */
    public $debugSql;
    /**
     * In order to prevent errors with output and headers, set this variable <tt>TRUE</tt> after the headers are sent.
     *
     * @var bool
     */
    protected $_safePoint;
    /**
     * Array containing the activity log for queries, filenames and their processing time.
     *
     * @var array
     */
    protected $_log = [];
    /**
     * Stores the start time which is used to calculate the processing time. Use the method startTime() to set this variable.
     *
     * @var float|array
     */
    protected $_startTime;
    /**
     * Indicates the current template file loaded. Used on the showToolbar() method.
     *
     * @var bool
     */
    protected $_templateFilename;
    protected $_exceptionsEnabled = false;
    protected $_maintenancePage;

    /**
     * Public Constructor, it checks the flags and settings, will do nothing if $c_jp7 is <tt>FALSE</tt>.
     *
     * @global bool
     */
    public function __construct()
    {
        global $c_jp7;
        if (!$c_jp7) {
            return; // Only by Devs
        }
        $this->_startTime[] = $_SERVER['REQUEST_TIME'];
        // Debug - SQL
        $this->debugSql = !empty($_GET['debug_sql']);
        // Debug - Filename
        if (isset($_GET['debug_filename'])) {
            setcookie('debug_filename', $_GET['debug_filename'], 0, '/');
            $_COOKIE['debug_filename'] = $_GET['debug_filename'];
        }
        if (!empty($_COOKIE['debug_filename'])) {
            $this->debugFilename = $_COOKIE['debug_filename'];
        }
        // Debug - Toolbar
        if (isset($_GET['debug_toolbar'])) {
            setcookie('debug_toolbar', $_GET['debug_toolbar'], 0, '/');
            $_COOKIE['debug_toolbar'] = $_GET['debug_toolbar'];
        }
    }
    /**
     * Starts recording the time spent on the code. When using more than one startTime(), the time will be displayed from the last to the first when getTime() is called.
     */
    public function startTime()
    {
        $debug_mtime = explode(' ', microtime());
        $this->_startTime[] = $debug_mtime[1] + $debug_mtime[0];
    }
    /**
     * Calculates and displays the time spent from the moment startTime() was called.
     *
     * @param bool Sets whether the time will be outputted or not.
     */
    public function getTime($output = false, $msg = 'Processed in')
    {
        if (!count($this->_startTime)) {
            return;
        }
        $debug_mtime = explode(' ', microtime());
        // Retrieves and deletes the last value
        $debug_starttime = array_pop($this->_startTime);
        $debug_totaltime = round(($debug_mtime[0] + $debug_mtime[1] - $debug_starttime) * 1000);
        if ($output && $this->isSafePoint()) {
            echo '<div class="debug_msg">'.$msg.': '.$debug_totaltime.'ms.</div>';
        }

        return $debug_totaltime;
    }
    public function step($name, $tag = 'step')
    {
        $this->addLog($name, $tag, $this->getTime());
        $this->startTime();
    }

    public function finish($name, $tag = 'step')
    {
        $this->addLog($name, $tag, $this->getTime());
    }
    /**
     * Shows the filename if $safePoint and $debugFilename are <tt>TRUE</tt>. Adds the filename to $_log.
     *
     * @param string $filename Name of the file.
     *
     * @return string Returns the $filename value unchanged.
     *
     * @global string
     */
    public function showFilename($filename)
    {
        if ($this->debugFilename && $this->isSafePoint()) {
            echo '<div class="debug_msg">'.str_replace(BASE_PATH, '/', str_replace('\\', '/', $filename)).'</div>';
        }
        /*
        if ($this->active) {
            // Creates a new log entry for this file
            $this->addLog($filename, 'file');
        }
        */
        return $filename;
    }
    /**
     * Formats and displays an SQL query.
     *
     * @param string $sql        SQL query to be formatted and displayed.
     * @param bool   $forceDebug If <tt>TRUE</tt> it will show the SQL even when $_GET['debug_sql'] is not set, the default value is <tt>FALSE</tt>.
     * @param string Stylesheet on the box displayed. The default value is ''.
     */
    public function showSql($sql, $time, $forceOutput = false, $style = '')
    {
        if ($time > 5) {
            $this->addLog($sql, 'sql', $time);
        }
        if ($this->isSafePoint() || $forceOutput) {
            echo $this->syntaxHighlightSql($sql, $style);
        }
    }

    public function syntaxHighlightSql($sql, $style = '')
    {
        return '<div class="debug_sql" style="'.$style.'">'.SqlFormatter::format($sql).'</div>';
    }

    /**
     * Formats and returns the backtrace.
     *
     * @param string $msgErro   Error message (optional).
     * @param string $sql       SQL query which was executed (optional).
     * @param array  $backtrace Backtrace generated by debug_backtrace() (optional).
     *
     * @return string Formatted HTML backtrace.
     */
    public function getBacktrace($msgErro = null, $sql = null, $backtrace = null)
    {
        $S = '';
        $sqlErrorPattern = '/(.*)right syntax to use near \'(.*)\' at line(.*)/';

        if ($msgErro) {
            if ($sql && preg_match($sqlErrorPattern, $msgErro, $matches)) {
                $sql = str_replace($matches[2], '/* <--- SYNTAX ERROR */'.$matches[2], $sql);
            }
            $S .= $this->_getBacktraceLabel('ERRO').wordwrap($msgErro, 85, "\n")."\n";
        }

        $S .= $this->getBasicBacktrace($backtrace);

        $S .= '<hr />';
        if ($sql) {
            $S .= $this->_getBacktraceLabel('SQL').$this->syntaxHighlightSql($sql)."\n";
        }
        $S .= $this->_getBacktraceLabel('URL').(($_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://').$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']."\n";
        if ($_SERVER['HTTP_REFERER']) {
            $S .= $this->_getBacktraceLabel('REFERER').$_SERVER['HTTP_REFERER']."\n";
        }
        $S .= $this->_getBacktraceLabel('IP CLIENTE').$_SERVER['REMOTE_ADDR']."\n";
        $S .= $this->_getBacktraceLabel('IP SERVIDOR').$_SERVER['SERVER_ADDR']."\n";
        $S .= $this->_getBacktraceLabel('USER_AGENT').$_SERVER['HTTP_USER_AGENT']."\n";
        $S .= '<hr />';
        if (count($_POST)) {
            $S .= $this->_getBacktraceLabel('POST').print_r($_POST, true);
        }
        if (count($_GET)) {
            $S .= $this->_getBacktraceLabel('GET').print_r($_GET, true);
        }
        if (count($_SESSION)) {
            $S .= $this->_getBacktraceLabel('SESSION').print_r($_SESSION, true);
        }
        if (count($_COOKIE)) {
            $S .= $this->_getBacktraceLabel('COOKIE').print_r($_COOKIE, true);
        }
        if (class_exists('Jp7_Interadmin_Soap') && Jp7_Interadmin_Soap::isSoapRequest()) {
            $S .= $this->_getBacktraceLabel('HTTP_SOAPACTION').print_r($_SERVER['HTTP_SOAPACTION'], true);
            $S .= $this->_getBacktraceLabel('CONTENT_TYPE').print_r($_SERVER['CONTENT_TYPE'], true);
            $S .= $this->_getBacktraceLabel('PHP INPUT').htmlspecialchars(print_r(file_get_contents('php://input'), true));
        }

        return '<pre style="background-color:#FFFFFF;font-size:11px;text-align:left;">'.$S.'</pre>';
    }

    public function getBasicBacktrace($backtrace = null)
    {
        if (!$backtrace) {
            $backtrace = debug_backtrace();
        }
        krsort($backtrace);

        $html = '<hr />';
        $html .= $this->_getBacktraceLabel('CALL STACK').'<br />';
        $html .= '<table class="jp7_debugger_table"><tr><th>#</th><th>Function</th><th>Location</th></tr>';
        foreach ($backtrace as $key => $row) {
            $html .= '<tr><td>'.(count($backtrace) - $key).'</td>';
            $html .= '<td>'.$row['class'].$row['type'].$row['function'].'()</td>';
            $html .= '<td>'.str_replace(BASE_PATH, '', $row['file']).':'.$row['line'].'</td></tr>';
        }
        $html .= '</table>';

        return $html;
    }

    /**
     * Adds padding and html formatting to the backtrace label.
     *
     * @param string $caption Label caption.
     *
     * @return string Formatted label.
     */
    protected function _getBacktraceLabel($caption)
    {
        return '<strong style="color:red">'.str_pad($caption, 12, ' ', STR_PAD_LEFT).':</strong> ';
    }
    /**
     * Lança exceções em caso de erro de SQL, ao invés de utilizar a função jp7_debug().
     *
     * @param bool $bool
     */
    public function setExceptionsEnabled($bool)
    {
        $this->_exceptionsEnabled = $bool;
    }
    /**
     * @return bool
     */
    public function isExceptionsEnabled()
    {
        return $this->_exceptionsEnabled;
    }
    /**
     * Method to be used as default error handler with set_error_handler() function.
     *
     * @param $code Error type code, like E_STRICT, E_NOTICE and so on.
     * @param $msgErro Error message.
     *
     * @return bool
     */
    public function errorHandler($code, $msgErro)
    {
        if ($code == E_WARNING && strpos($msgErro, 'Creating default object from empty value') !== false) {
            return true; // Ignorar esse erro do PHP 5.4
        }

        return false;
    }
    /**
     * Adds a log to the $_log array.
     *
     * @param string $value Value to be displayed.
     * @param string $tag   Tag which represents the type of data stored.
     * @param int    $time  Time this proccess took in miliseconds, e.g. ammount of time a SQL query took to be executed.
     */
    public function addLog($value, $tag = 'log', $time = null)
    {
        $this->_log[] = ['tag' => $tag, 'value' => $value, 'time' => $time];
    }
    /**
     * Returns the log array.
     *
     * @param string $value Value to be displayed.
     * @param string $tag   Tag which represents the type of data stored.
     * @param int    $time  Time this proccess took in miliseconds, e.g. ammount of time a SQL query took to be executed.
     *
     * @return array Returns the value of $_log.
     */
    public function getLog()
    {
        return $this->_log;
    }
    /**
     * Sets the filename of the current template.
     *
     * @param string $filename Name of the current template file.
     */
    public function setTemplateFilename($filename)
    {
        $this->_templateFilename = $filename;
    }
    /**
     * Returns the filename of the current template.
     *
     * @return string Name of the current template file.
     */
    public function getTemplateFilename()
    {
        return $this->_templateFilename;
    }
    /**
     * Displays current template, log data, and time for the page.
     */
    public function showToolbar()
    {
        if (($_COOKIE['debug_toolbar'] || $this->debugSql || $this->debugFilename) && $this->isSafePoint()) {
            if ($this->_templateFilename) {
                echo('Template: '.$this->_templateFilename);
            } else {
                echo('PHP_SELF: '.$_SERVER['PHP_SELF']);
            }
            $this->getTime(true);
            echo $this->getLogTable();
        }
    }

    public function getLogTable()
    {
        $table = Jp7_Tag_Table::fromArray($this->_log);

        return $table->attr('border', 1)->attr('class', 'jp7_debugger_table debug-toolbar');
    }

    public function isSafePoint()
    {
        return $this->_safePoint || headers_sent();
    }
    public function setSafePoint($bool)
    {
        $this->_safePoint = $bool;
    }
    /**
     * Envia o trace do erro para debug+CLIENTE@jp7.com.br.
     *
     * @param string|Exception $backtraceOrException
     *
     * @return bool
     * @deprecated
     */
    public function sendTraceByEmail($backtraceOrException)
    {
        trigger_error('Use Sentry and Log::error() instead', E_USER_DEPRECATED);
    }

    /**
     * Returns $maintenancePage.
     *
     * @see Jp7_Debugger::$maintenancePage
     */
    public function getMaintenancePage()
    {
        return $this->_maintenancePage ?: DEFAULT_PATH.'/index_manutencao.htm';
    }
    /**
     * Sets $maintenancePage.
     *
     * @param object $maintenancePage
     *
     * @see Jp7_Debugger::$maintenancePage
     */
    public function setMaintenancePage($maintenancePage)
    {
        $this->_maintenancePage = $maintenancePage;
    }
}
