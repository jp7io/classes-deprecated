<?php

class Jp7_Cache_Backend_File extends Zend_Cache_Backend_File
{
    /**
     * @see Zend_Cache_Backend_File::__construct()
     */
    public function __construct(array $options = [])
    {
        $options += [
            'cache_file_perm' => 0777,
            'file_name_prefix' => 'zf',
            'hashed_directory_level' => 1,
            'hashed_directory_perm' => 0777,
        ];

        parent::__construct($options);
    }

    protected function _idToFileName($id)
    {
        return parent::_idToFileName($id).'.cache';
    }
}
