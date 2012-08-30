<?php

require '../bootstrap.php';

$config = new Zend\Config\Config(array(
    
    'queueServer' => array(
        'host' => 'localhost', 
        'port' => 5672, 
        'user' => 'mcu', 
        'password' => 'mcuapi', 
        'vhost' => '/mcu'
    ), 
    
    'rpc' => array(
        'brokerConsumerTag' => 'rpc-broker',
        'agentConsumerTag' => 'rpc-agent',
        
        'request' => array(
            'queueName' => 'rpc-request', 
            'exchangeName' => 'mcu-http-serializer'
        ),
        
        'response' => array(
            'queuePrefix' => 'rpc-response-', 
            'exchangeName' => 'mcu-http-serializer'
        )
    )
));


function _dump ($value)
{
    error_log(print_r($value, true));
}