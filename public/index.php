<?php

/**
 * 入口文件
 */
require __DIR__.'/../vendor/autoload.php';

defined('PUBLIC_PATH') or define('PUBLIC_PATH',dirname($_SERVER['SCRIPT_FILENAME']).'/');
defined('ROOT_PATH') or define('ROOT_PATH',PUBLIC_PATH.'../');
defined('RUNTIME_PATH') or define('RUNTIME_PATH', ROOT_PATH.'/runtime/');

$config = include_once __DIR__.'/../app/config.php';
\App\Application::getInstance($config)->run();
