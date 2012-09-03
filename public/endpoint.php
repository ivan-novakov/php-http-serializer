<?php

use Zend\Http\PhpEnvironment;
use HttpSer\Agent;

define('HTTPSER_ENDPOINT_DIR', dirname(__FILE__) . '/');

require HTTPSER_ENDPOINT_DIR . '../bootstrap.php';

$globalConfig = new \Zend\Config\Config(require HTTPSER_DIR . 'config/endpoint.cfg.php');

$request = new PhpEnvironment\Request();

$agent = new Agent\Agent($globalConfig->agent);
$agent->connect();

$msgBody = serialize($request);

try {
    $responseData = $agent->sendMessage($msgBody);
} catch (Exception $e) {
    // handle error
    _dump(sprintf("[%s] %s", get_class($e), $e->getMessage()));
    exit();
}

$response = unserialize($responseData);
if (false === $response) {
    // handle error
    _dump('unserialize error');
    exit();
}

//$response = new \Zend\Http\Response();
header($response->renderStatusLine());
$headers = $response->getHeaders()
    ->toArray();
foreach ($headers as $name => $value) {
    //_dump("$name: $value");
    header("$name: $value");
}
echo $response->getContent();
