<?php
require 'vendor/autoload.php';
$map = require 'vendor/composer/autoload_psr4.php';
var_dump($map['PhpMqtt\Client\\'] ?? 'Not found in map');
var_dump(class_exists('PhpMqtt\Client\ConnectionSettings'));
$file = $map['PhpMqtt\Client\\'][0] . '/ConnectionSettings.php';
var_dump(file_exists($file));
