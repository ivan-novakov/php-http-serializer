<?php

use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;

require 'common.php';

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