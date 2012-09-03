<?php

use HttpSer\Frontend\Frontend;

define('HTTPSER_ENDPOINT_DIR', dirname(__FILE__) . '/');

require HTTPSER_ENDPOINT_DIR . '../bootstrap.php';

$frontend = new Frontend(require HTTPSER_DIR . 'config/endpoint.cfg.php');
$frontend->run();

