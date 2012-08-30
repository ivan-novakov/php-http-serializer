<?php

namespace HttpSer\Broker;

use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;

require 'common.php';

$brokerConfig = new \Zend\Config\Config(array(
    
    'connection' => array(
        'host' => 'localhost', 
        'port' => 5672, 
        'user' => 'mcu', 
        'password' => 'mcuapi', 
        'vhost' => '/mcu'
    ), 
    
    'bindings' => array(
        'exchange' => array(
            'name' => 'mcu-http-serializer', 
            'options' => array(
                'type' => 'direct', 
                'passive' => false, 
                'durable' => true, 
                'autoDelete' => false
            )
        ), 
        'queue' => array(
            'name' => 'rpc-request', 
            'options' => array(
                'passive' => false, 
                'durable' => true, 
                'exclusive' => false, 
                'autoDelete' => true
            )
        ), 
        'consumer' => array(
            'tag' => 'rpc-broker', 
            'noLocal' => false, 
            'noAck' => true, 
            'exclusive' => true, 
            'noWait' => false
        )
    ), 
    
    'handler' => array(
        'name' => 'Dummy', 
        'params' => array()
    )
));

$broker = new Broker($brokerConfig);
$handler = new Handler\Dummy($brokerConfig->handler->params);

$broker->setHandler($handler);
$broker->start();

exit();
$serverConfig = $config->queueServer;
$reqConfig = $config->rpc->request;
$respConfig = $config->rpc->response;

$conn = new AMQPConnection($serverConfig->host, $serverConfig->port, $serverConfig->user, $serverConfig->password, $serverConfig->vhost);
$ch = $conn->channel();

/* Request queue */
$ch->queue_declare($reqConfig->queueName, false, true, false, true);
$ch->exchange_declare($reqConfig->exchangeName, 'direct', false, true, true);
$ch->queue_bind($reqConfig->queueName, $reqConfig->exchangeName);

$callback = function  ($msg)
{
    global $ch, $respConfig;
    
    $ch = $msg->delivery_info['channel'];
    
    //_dump($msg);
    _dump($msg->body);
    $msgBody = "RESPONSE: " . $msg->body;
    
    $respMsg = new AMQPMessage($msgBody, array(
        'content_type' => 'text/plain', 
        'delivery_mode' => 2, 
        'correlation_id' => $msg->get('correlation_id')
    ));
    
    $ch->basic_publish($respMsg, $respConfig->exchangeName, $msg->get('reply_to'));
};

$ch->basic_consume($reqConfig->queueName, $config->rpc->brokerConsumerTag, false, true, true, false, $callback);


function shutdown ($ch, $conn)
{
    $ch->close();
    $conn->close();
}

register_shutdown_function('shutdown', $ch, $conn);

// Loop as long as the channel has callbacks registered
while (count($ch->callbacks)) {
    $ch->wait();
}