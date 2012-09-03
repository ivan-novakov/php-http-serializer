<?php

namespace HttpSer\Broker;

use HttpSer\Log\Log;
use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;

define('HTTPSER_BROKER_DIR', dirname(__FILE__) . '/');

require HTTPSER_BROKER_DIR . '../bootstrap.php';

$globalConfig = new \Zend\Config\Config(require HTTPSER_DIR . 'config/broker.cfg.php');

$broker = new Broker($globalConfig->broker);

$logger = new \Zend\Log\Logger();
$logger->addWriter('stream', null, array(
    'stream' => 'php://output'
));
$log = new Log($logger);
$broker->addObserver($log);

$handlerConfig = $globalConfig->handler;
$handler = HandlerFactory::factory($handlerConfig->className, $handlerConfig->params);
$broker->setHandler($handler);

$broker->start();