<?php

use Illuminate\Contracts\Cache\Store;
use \Illuminate\Cache\FileStore;
use \Illuminate\Cache\Repository;
use \Illuminate\Filesystem\Filesystem;

class InterAdminCacheFacade extends Illuminate\Support\Facades\Cache
{
    private static $cache;

    // Temporario para usar facade sem Laravel
    protected static function resolveFacadeInstance($name)
    {
        if (!self::$cache) {
            $filestore = new FileStore(new Filesystem(), BASE_PATH.'/cache');
            self::$cache = new Repository($filestore);
        }
        return self::$cache;
    }

    public static function store()
    {
        return self::$cache;
    }

    public static function repository(Store $filestore)
    {
        return new Repository($filestore);
    }
}
