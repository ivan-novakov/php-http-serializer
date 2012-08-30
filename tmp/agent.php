<?php

use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;

require 'common.php';

$agentId = $_SERVER['argv'][1];
$msgBody = $_SERVER['argv'][2];

$serverConfig = $config->queueServer;
$reqConfig = $config->rpc->request;
$respConfig = $config->rpc->response;

$conn = new AMQPConnection($serverConfig->host, $serverConfig->port, $serverConfig->user, $serverConfig->password, $serverConfig->vhost);
$ch = $conn->channel();

/* Request queue */
//$ch->queue_declare($reqConfig->queueName, false, true, false, true);
//$ch->exchange_declare($reqConfig->exchangeName, 'direct', false, true, true);
//$ch->queue_bind($reqConfig->queueName, $reqConfig->exchangeName, $reqConfig->routingKey);

/* Response queue */
$queueData = $ch->queue_declare(uniqid($respConfig->queuePrefix), false, false, true, true);
$queueName = $queueData[0];
//$ch->exchange_declare($respConfig->exchangeName, 'direct', false, true, true);
$ch->queue_bind($queueName, $respConfig->exchangeName, $queueName);

$msgBody = sprintf("Agent [%s]: %s", $agentId, $msgBody);
$msg = new AMQPMessage($msgBody, array(
    'content_type' => 'text/plain', 
    'delivery_mode' => 2, 
    'correlation_id' => uniqid(), 
    'reply_to' => $queueName
));

usleep(rand(1, 200));
$ch->basic_publish($msg, $reqConfig->exchangeName);

$reply = false;
$callback = function  ($msg)
{
    global $reply;
    
    //_dump($msg);
    _dump($msg->body);
    
    $reply = true;
};


$ch->basic_consume($queueName, $config->rpc->agentConsumerTag, false, true, false, false, $callback);

while (! $reply) {
    $ch->wait();
}

$ch->close();
$conn->close();