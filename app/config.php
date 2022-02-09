<?php

//配置文件
return [
    'debug'           =>true,
    'timezone'        => 'PRC',
    'log_level'       => 'info',
    'redis'=>[
        'host'        => '127.0.0.1',
        'port'        => '6379',
        'auth'        => '', //密码
        'seldb'       => ''
     ],
    'middleware' => [],
    'database'=>[
        'default'=>[
            'database_type' => 'mysql',
            'server'        => '127.0.0.1',
            'port'          => 3306,
            'database_name' => 'test',
            'username'      => 'root',
            'password'      => '',
            'charset'       => 'utf8',
            'option'        => [PDO::ATTR_CASE => PDO::CASE_NATURAL, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION],
        ],
    ],
];