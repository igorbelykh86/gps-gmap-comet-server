<?php

use MyServer\Server;

define('APP', __DIR__);
define('CLASSES', APP . '/classes');
require APP . '/config.php';
require_once APP . '/autoloader.php';

date_default_timezone_set(TIMEZONE);

$server = new Server(SERVER_IP, SERVER_PUT_PORT, SERVER_READ_PORT);
$server->setMaxPutClients(MAX_PUT_CLIENTS);
$server->setMaxReadClients(MAX_READ_CLIENTS);
$server->start();
