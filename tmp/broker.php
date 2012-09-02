<?php

namespace HttpSer\Broker;
use HttpSer\Log\Log;
use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;
require 'common.php';

$globalConfig = new \Zend\Config\Config(
        array(
            'broker' => array(
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
                )
            ),
            
            'handler' => array(
                'className' => '\HttpSer\Broker\Handler\HttpsRelay',
                'params' => array(
                    'targetUrl' => 'https://hroch.cesnet.cz/test/target.php',
 
                    'client' => array(
                        
                        'options' => array(
                            'keepalive' => true,
                            'useragent' => 'HTTP Serializer'
                        ),
                        
                        'adapter' => array(
                            'class' => 'Zend\Http\Client\Adapter\Socket',
                            'options' => array(
                                'persistent' => true,
                                'ssltransport' => 'ssl'
                            ),
                            'streamContext' => array(
                                'ssl' => array(
                                    'verify_peer' => true,
                                    'cafile' => '/etc/ssl/certs/tcs-ca-bundle.pem',
                                    'allow_self_signed' => false
                                )
                            )
                        )
                    )
                )
            )
        ));

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
