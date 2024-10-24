<?php

use Jp7_Interadmin_Upload_AdapterInterface as AdapterInterface;

class Jp7_Interadmin_Upload
{
    /**
     * @var AdapterInterface
     */
    protected $adapter;

    /**
     * @var AdapterInterface
     */
    protected $config;

    public function __construct($config, $adapter = null)
    {
        $this->config = $config;
        $this->adapter = $this->getAdapter($adapter);
    }

    /**
     * Altera o endereço para que aponte para a url do cliente.
     *
     * @param $url Url do arquivo.
     *
     * @return string
     */
    public function url($path = '../../', $template = 'original')
    {
        if ($this->isExternal($path)) {
            // Not an upload path => Wont change
            return $path;
        }
        $path = substr($path, strlen('../../'));

        if ($this->isImage($path) && ($template !== 'original' || !$this->isGif($path))) {
            return $this->adapter->imageUrl($path, $template);
        } else {
            return $this->adapter->url($path);
        }
    }

    public function hasPurging()
    {
        return $this->adapter->hasPurging();
    }

    public function purge($path)
    {
        $path = substr($path, strlen('../../'));
        return $this->adapter->purge($path);
    }

    public function getHumanSize($path)
    {
        try {
            return jp7_human_size($this->getSize($path));
        } catch (RuntimeException $e) {
            return '0KB';
        }
    }

    public function getSize($path)
    {
        if ($this->isExternal($path)) {
            return;
        }
        $path = substr($path, strlen('../../'));
        return Storage::size($path);
    }

    public function isImage($url)
    {
        return preg_match('/.(jpg|jpeg|png|gif)[#?]?[^?\/#]*$/i', $url);
    }

    public function isGif($url)
    {
        return preg_match('/.gif[#?]?[^?\/#]*$/i', $url);
    }


    public function getAdapter()
    {
        $imagecache = $this->config->imagecache ?? null;
        if (empty($this->adapter)) {
            if (empty($imagecache))
                $this->adapter = new Jp7_Interadmin_Upload_Legacy($this->config);
            } elseif ($imagecache === 'imgix') {
                $this->adapter = new Jp7_Interadmin_Upload_Imgix($this->config);
            } elseif ($imagecache) {
                $this->adapter = new Jp7_Interadmin_Upload_Intervention($this->config);
        }
        return $this->adapter;
    }

    protected static function isExternal($path)
    {
        return !str_starts_with($path, '../../upload/');
    }
}
