<?php

return array(
    'debug' => true,
    "app_name" => "app name",
    "session_key" => array(
//        "member" => "member",
        "privilege" => "privilege",
    ),
    "providers" => array(
        '\App\Provider\Error',
        '\App\Provider\Session',
        'App\Provider\Router',
        'App\Provider\Connection',
        '\App\Provider\Log',
        '\App\Provider\FileCache',

    ),
    "console_providers" => array(
        '\App\Provider\Error',
        '\App\Provider\Session',
        'App\Provider\Connection',
        '\App\Provider\Log',
        '\App\Provider\FileCache',
    ),
);
