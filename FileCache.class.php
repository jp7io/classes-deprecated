<?
/**
 * FileCache class, used to store copies of pages to save database connections and processing time.
 * 
 * @author Carlos Rodrigues
 * @copyright Copyright 2002-2008 JP7 (http://jp7.com.br)
 * @version 1.0 (2008/06/16)
 * @package FileCache
 */
 
/**
 * class FileCache
 *
 * @package FileCache
 */
class FileCache{
	/**
	 * @var string Site root directory.
	 */
	public $fileRoot;
	/**
	 * @var string Path used to store cached files.
	 */
	public $cachePath;
	/**
	 * @var string Name of the file to be cached or loaded from cache.
	 */
	public $cacheFileName;
	/**
	 * Constructor function, defines the path and filename and starts caching or loading it.
	 *
	 * @param mixed $storeId ID of the file. Only needed if the same page has different data deppending on the ID.
	 * @param string $cachePath Sets the directory where the cache will be saved, the default value is 'cache'.
	 * @global string
 	 * @global string
 	 * @global bool
  	 * @global bool
	 * @return FileCache
	 */	
	public function __construct($storeId = FALSE, $cachePath = 'cache') {
		global $c_root, $c_path, $c_cache, $s_interadmin_preview, $interadmin_gerar_menu;
		
		if ($s_interadmin_preview || $interadmin_gerar_menu || !$c_cache) return;
		
		$this->fileRoot = $c_root;
		$this->cachePath = $this->fileRoot . $cachePath . '/';
		$this->fileName = substr($_SERVER['REQUEST_URI'], strlen($c_path) + 1);
		
		$pos_query = strpos($this->fileName, '?');
		if ($pos_query) $this->fileName = substr($this->fileName, 0, $pos_query);
		$this->fileName = jp7_path($this->fileName, TRUE);
				
		$pathinfo = pathinfo($this->cachePath . $this->fileName);
		
		if ($storeId){
			if ($pathinfo['extension']) $ext = '.' . $pathinfo['extension'];
			$this->fileName = dirname($this->fileName) . '/' . basename($this->fileName, $ext) . '/' . $storeId . $ext;
		}
		
 		if ($pathinfo['extension']) $this->fileName .= '.cache';
		else $this->fileName .= '/index.cache';
		
		//$_SERVER['PHP_SELF'] = $this->fileName;
				
		if ($this->checkLog() && !$_GET['nocache_force']) $this->getCache();
		else $this->startCache();
	}
	/**
	 * Starts caching the current file
	 *
	 * @return NULL
	 */	
	public function startCache() {
		header('pragma: no-cache');
		ob_start();
	}
	/**
	 * Stops caching and saves the current file, the file is saved without line breaks and spaces and with a commentary saying when it was published.
	 *
	 * @return NULL
	 */	
	public function endCache() {
		if (!$this->fileName) return;
		$file_content = ob_get_contents();
		$file_content = str_replace(chr(9), '', $file_content);
		$file_content = str_replace(chr(13), '', $file_content);
		if (strlen($file_content) > 10) {
			$dir_arr = explode('/', $this->fileName);
			array_pop($dir_arr);
			$dir_path = '';
			foreach ($dir_arr as $dir) {
					$dir_path .= $dir . '/';
					if (!is_dir($this->cachePath . $dir_path)) mkdir($this->cachePath . $dir_path);
			}
			$file = @fopen($this->cachePath . $this->fileName, 'w');
			$file_content .= "\n" . '<!-- Published by JP7 InterAdmin in ' . date('Y/m/d - H:i:s') . ' -->';
			@fwrite($file, $file_content);
		}
		ob_end_flush();
	}
	/**
	 * Opens the cached file and outputs it.
	 *
	 * @return NULL
	 */	
	public function getCache() {
		global $debugger;
		readfile($this->cachePath . $this->fileName);
		if ($debugger){
			echo $debugger->debugStyle;
			$debugger->showFilename('File from cache: ' . $this->cachePath . $this->fileName);
		}
		exit();
	}
	/**
	 * Checks if the log file is newer than the cached file,  and if the cached file is older than 1 day.
	 *
	 * @return bool
	 */	
	public function checkLog() {
		$filemtime = @filemtime($this->cachePath . $this->fileName);
		if (@filemtime($this->fileRoot . 'interadmin.log') < $filemtime && date('d', $filemtime) == date('d')) return TRUE;
	}
}
?>