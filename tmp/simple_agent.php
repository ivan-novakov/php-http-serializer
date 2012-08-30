<?php

use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;

require 'common_simple.php';

$serverConfig = $config->queueServer;
$reqConfig = $config->rpc;

$conn = new AMQPConnection($serverConfig->host, $serverConfig->port, $serverConfig->user, $serverConfig->password);
$ch = $conn->channel();

$ch->queue_declare($reqConfig->queueName, false, true, false, true);
$ch->exchange_declare($reqConfig->exchangeName, 'direct', false, true, true);

$ch->queue_bind($reqConfig->queueName, $reqConfig->exchangeName);

$msgBody = implode(' ', array_slice($argv, 1));

$msg = new AMQPMessage($msgBody, array(
    'content_type' => 'text/plain', 
    'delivery_mode' => 2
));

$ret = $ch->basic_publish($msg, $reqConfig->exchangeName);
_dump($ret);

$ch->close();
$conn->close();