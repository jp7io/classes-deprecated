<?php

class Jp7_Interadmin_Upload_Imgix extends Jp7_Interadmin_Upload_AdapterAbstract
{

    private $config;

    public function __construct($config)
    {
        $this->config = $config;
        parent::__construct($config);
    }

    // IMGIX_HOST/upload/bla/123?w=40&h=40
    public function imageUrl($path, $template)
    {
        $url = $this->url($path);

        // Replace host
        $url = $this->setHost($url, $this->config->imgix['host']);

        $params = $this->config->imgix['templates'][$template];
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
        $jp7InteradminUpload = new Jp7_Interadmin_Upload($this->config);

        if (!$jp7InteradminUpload->isImage($path) || !isset($config->imgix['api_key'])) {
            return;
        }
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'timeout' => 2, // Fire and forget
                'header'  => [
                    'Content-Type: application/vnd.api+json',
                    'Authorization: Bearer ' . $config->imgix['api_key']
                ],
                'content' => json_encode([
                    'data' => [
                        'attributes' => [
                            'url' => $this->imageUrl($path, 'original'),

                        ],
                        'type' => 'purges'
                    ]
                ])
            ]
        ]);
        try {
            file_get_contents('https://api.imgix.com/api/v1/purge', false, $context);
        } catch (Exception $e) {
            // ignore because of Fire and forget timeout
            Log::info($e);
        }
    }
}
