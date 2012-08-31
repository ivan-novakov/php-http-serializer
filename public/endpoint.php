<?php

use Zend\Http\PhpEnvironment;
use HttpSer\Agent;

require '../bootstrap.php';

$globalConfig = new \Zend\Config\Config(array(
    
    'agent' => array(
        
        'connection' => array(
            'host' => 'localhost', 
            'port' => 5672, 
            'user' => 'mcu', 
            'password' => 'mcuapi', 
            'vhost' => '/mcu'
        ), 
        
        'bindings' => array(
            'exchange' => array(
                'name' => 'mcu-http-serializer'
            ), 
            'queue' => array(
                'namePrefix' => 'rpc-response-', 
                'options' => array(
                    'passive' => false, 
                    'durable' => false, 
                    'exclusive' => true, 
                    'autoDelete' => true
                )
            ), 
            'consumer' => array(
                'tag' => 'rpc-agent', 
                'noLocal' => false, 
                'noAck' => true, 
                'exclusive' => false, 
                'noWait' => false
            )
        )
    )
));

$request = new PhpEnvironment\Request();

$agent = new Agent\Agent($globalConfig->agent);
$agent->connect();

$msgBody = serialize($request);
$responseData = $agent->sendMessage($msgBody);
$response = unserialize($responseData);
//$response = new Zend\Http\Response;
header($response->renderStatusLine());
echo $response->getBody();
