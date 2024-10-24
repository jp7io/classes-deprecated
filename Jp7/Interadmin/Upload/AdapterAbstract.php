<?php

abstract class Jp7_Interadmin_Upload_AdapterAbstract implements Jp7_Interadmin_Upload_AdapterInterface
{

    private $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function url($path)
    {
        return $this->getScheme().'://'.$this->config->storage['host'].'/'.
            ($this->config->storage['path'] ? $this->config->storage['path'].'/' : '') .
            $path;
    }

    public function hasPurging()
    {
        return true;
    }

    protected function getScheme()
    {
        return $this->config->storage['scheme'] ?? 'http'.(isset($_SERVER['HTTPS']) ? 's' : '');
    }

    protected function setHost($url, $host)
    {
        // Replace host
        return replace_prefix(
            $this->url(''),
            $this->getScheme().'://'.$host.'/',
            $url
        );
    }

    protected function mergeQuery($url, $params)
    {
        $query = http_build_query($params);
        if (!$query) {
            return $url;
        }
        return $url . (str_contains($url, '?') ? '&' : '?') . $query;
    }
}
