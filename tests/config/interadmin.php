<?php

return [
    // Prefix of the class: Client_Record, Client_Type
    'namespace' => 'Test_',
    // Whether records need the field "publish" checked
    'preview' => true,
    // Options for uploaded files:
    'storage' => [
        // Prefix path InterAdmin saves
        'backend_path' => '../..',
        // Prefix path on the URL for the browser
        'path' => '',
        // Storage HOST
        'host' => 'example.org',
        'scheme' => 'https',
    ],
    // Which host Interadmin can be found on
    'host' => 'interadmin.jp7.com.br'
];
