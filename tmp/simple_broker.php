<?php

use PhpAmqpLib\Connection\AMQPConnection;

require 'common_simple.php';

$serverConfig = $config->queueServer;
$reqConfig = $config->rpc;

$conn = new AMQPConnection($serverConfig->host, $serverConfig->port, $serverConfig->user, $serverConfig->password);
$ch = $conn->channel();

$ch->queue_declare($reqConfig->queueName, false, true, false, true);
$ch->exchange_declare($reqConfig->exchangeName, 'direct', false, true, true);

$ch->queue_bind($reqConfig->queueName, $reqConfig->exchangeName);


$callback = function ($msg)
{
    //_dump($msg);
    _dump($msg->body);
};

$ch->basic_consume($reqConfig->queueName, $reqConfig->consumerTag, false, true, true, false, $callback);


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