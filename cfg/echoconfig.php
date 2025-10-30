<?php

use cryodrift\fw\Core;

if (!isset($ctx)) {
    $ctx = Core::newContext(new \cryodrift\fw\Config());
}

$out = $ctx->config();

$out->addHandlerbefore(0, \cryodrift\fw\tool\Echoblocker::class, [
  'config' => [
    'sys\Main::path',
    'sys\Main::path_search',
    'src\mailviewer\db\SqliteStorage::getMessages',
    'src\mailviewer\Api::partview',
  ]
]);


