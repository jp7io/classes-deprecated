<?php
/*
$upload = new Jp7_Uploader('post');
if ($upload->hasFile('foto')) {
	$post->foto = $upload->save('foto', 'temp_foto.jpg');
}
*/

/**
 * Handler to help using $_FILES array.
 */
class Jp7_Uploader {
	protected $fieldName;
	protected $extensionsFilter;
	protected $typesFilter;
	protected $basePath = '../../upload/';
	
	/**
	 * Creates a new $_FILES handler.
	 * 
	 * @param string $fieldName Name of the submitted field.
	 * @param string $extensionsFilter Regex for $extensions [optional]
	 * @param string $typesFilter Regex for $types [optional]
	 */
	public function __construct($fieldName, $extensionsFilter = '/\.(jp[e]?g|gif|png|bmp)$/i', $typesFilter = '/image\/([p]?jp[e]?g|gif|png|bmp)/i') {
		$this->fieldName = $fieldName;
		$this->extensionsFilter = $extensionsFilter;
		$this->typesFilter = $typesFilter;
	}
	
	/**
	 * Checks if the key has a valid file.
	 * 
	 * @param object $key
	 * @throws Jp7_Uploader_InvalidExtensionException  For type/extension not valid.
	 * @throws Exception For unknown upload error (browser, server, etc). 
	 * @return bool
	 */
	public function hasFile($key) {
		extract($_FILES[$this->fieldName]);
		if ($name[$key]) {
			if ($error[$key]) {
				throw new Exception('There was an error while uploading: ' . $name[$key]);
			}
			if (!preg_match($this->extensionsFilter, $name[$key]) || !preg_match($this->typesFilter, $type[$key])) {
				throw new Jp7_Uploader_InvalidExtensionException($name[$key] . ' is not in a valid extension/type.');
			}
			return true;
		}
		return false;
	}
	/**
	 * Saves the file to the given destination (keeping the uploaded extension).
	 * 
	 * @param object $key
	 * @param object $destination
	 * @return string Full destination path (Base + Destination + Uploaded extension).
	 */
	public function save($key, $destination) {
		extract($_FILES[$this->fieldName]);
		
		$finalDestination = $this->getBasePath() . $destination . preg_replace('/(.*)(\.[^\.]*)$/', '\2', $name[$key]);
		
		// Mkdir if needed
		if (!is_dir(dirname($finalDestination))) {
			@mkdir(dirname($finalDestination));
			@chmod(dirname($finalDestination), 0777);
		}
		
		// Copy
		copy($tmp_name[$key], $finalDestination);
		@chmod($finalDestination, 0777);
		
		return $finalDestination;
	}
    /**
     * Returns $fieldName.
     *
     * @see Jp7_Uploader::$fieldName
     */
    public function getFieldName() {
        return $this->fieldName;
    }
    /**
     * Sets $fieldName.
     *
     * @param object $fieldName
     * @see Jp7_Uploader::$fieldName
     */
    public function setFieldName($fieldName) {
        $this->fieldName = $fieldName;
    }
    /**
     * Returns $extensionsFilter.
     *
     * @see Jp7_Uploader::$extensionsFilter
     */
    public function getExtensionsFilter() {
        return $this->extensionsFilter;
    }
    /**
     * Sets $extensionsFilter.
     *
     * @param object $extensionsFilter
     * @see Jp7_Uploader::$extensionsFilter
     */
    public function setExtensionsFilter($extensionsFilter) {
        $this->extensionsFilter = $extensionsFilter;
    }
    /**
     * Returns $typesFilter.
     *
     * @see Jp7_Uploader::$typesFilter
     */
    public function getTypesFilter() {
        return $this->typesFilter;
    }
    /**
     * Sets $typesFilter.
     *
     * @param object $typesFilter
     * @see Jp7_Uploader::$typesFilter
     */
    public function setTypesFilter($typesFilter) {
        $this->typesFilter = $typesFilter;
    }
    /**
     * Returns $basePath.
     *
     * @see Jp7_Uploader::$basePath
     */
    public function getBasePath() {
        return $this->basePath;
    }
    /**
     * Sets $basePath.
     *
     * @param object $basePath
     * @see Jp7_Uploader::$basePath
     */
    public function setBasePath($basePath) {
        $this->basePath = $basePath;
    }
}