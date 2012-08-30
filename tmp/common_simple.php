<?php

require '../bootstrap.php';

$config = new Zend\Config\Config(array(
    
    'queueServer' => array(
        'host' => 'localhost', 
        'port' => 5672, 
        'user' => 'guest', 
        'password' => 'guest', 
        'vhost' => '/'
    ), 
    
    'rpc' => array(
        'queueName' => 'rpc', 
        'exchangeName' => 'cesnet.rpc', 
        'consumerTag' => 'rpc-agent'
    )
));


function _dump ($value)
{
    error_log(print_r($value, true));
}