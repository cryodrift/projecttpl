<?php /** @noinspection DuplicatedCode */

//declare(strict_types=1);

namespace gfw;

use cryodrift\fw\Config;
use cryodrift\fw\Main;
use Phar;

require 'vendor/autoload.php';

//require_once __DIR__ . '/sys/Main.php';
Main::$rootdir = dirname(Phar::running(false)) ? dirname(Phar::running(false)) . '/' : __DIR__ . '/';
//Main::autoload('cryodrift\\fw', __DIR__.'/sys');
//Main::autoloader();

define('G_PHARFILE', basename(Phar::running()));
define('G_PHAR', 'phar://' . G_PHARFILE . '/');
define('G_PHARROOT', dirname(__FILE__));

Config::$includedirs = [
  '.',
  './',
  Main::$rootdir . 'src/',
  Main::$rootdir . 'sys/',
  Main::$rootdir . 'vendor/cryodrift/fw/',
  Main::$rootdir . 'vendor/cryodrift/',
  G_PHARROOT . '/src/',
  G_PHARROOT . '/sys/',
  G_PHARROOT . '/',
];
//print_r($includedirs);

set_include_path(implode(PATH_SEPARATOR, Config::$includedirs));
//echo 'index.php ' . get_include_path();
//Core::$log[0] = PHP_EOL.Core::toLog('loaded start', Core::time(true));
$config = Main::readConfig();
//Core::echo($config->getArrayCopy());
return Main::run($config);
