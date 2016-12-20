<?php

class Jp7_Interadmin_Upload_Imgix extends Jp7_Interadmin_Upload_AdapterAbstract
{

    // IMGIX_HOST/upload/bla/123?w=40&h=40
    public function imageUrl($path, $template)
    {
        global $config;

        $url = $this->url($path);

        // Replace host
        $url = $this->setHost($url, $config->imgix['host']);

        $params = $config->imgix['templates'][$template];
        if ($params) {
            $url = $this->mergeQuery($url, $params);
        }
        return (string) $url;
    }

    public function hasPurging()
    {
        global $config;
        return isset($config->imgix['api_key']);
    }

    public function purge($path)
    {
        global $config;
        if (!Jp7_Interadmin_Upload::isImage($path) || !isset($config->imgix['api_key'])) {
            return;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header'  => [
                    'Content-Type: application/x-www-form-urlencoded',
                    'Authorization: Basic '.base64_encode($config->imgix['api_key'].':')
                ],
                'content' => http_build_query([
                    'url' => $this->imageUrl($path, 'original')
                ])
            ]
        ]);

        file_get_contents('https://api.imgix.com/v2/image/purger', false, $context);
    }
}
