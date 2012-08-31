<?php

namespace HttpSer\Agent;
require 'common.php';

$agentId = $_SERVER['argv'][1];
$msgBody = $_SERVER['argv'][2];

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


$agent = new Agent($globalConfig->agent);
$agent->connect();

$msgBody = sprintf("Agent [%s]: %s", $agentId, $msgBody);
$response = $agent->sendMessage($msgBody);
_dump($response);
sleep(1);
$response = $agent->sendMessage('new message');
_dump($response);

$agent->disconnect();