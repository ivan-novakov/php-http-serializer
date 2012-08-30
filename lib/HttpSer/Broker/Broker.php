<?php

namespace HttpSer\Broker;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPConnection;


class Broker
{

    /**
     * Configuration object.
     * 
     * @var \Zend\Config\Config
     */
    protected $_config = NULL;

    /**
     * Connection object.
     * 
     * @var AMQPConnection
     */
    protected $_conn = NULL;

    /**
     * Channel object.
     * 
     * @var AMQPChannel
     */
    protected $_channel = NULL;


    /**
     * Constructor.
     * 
     * @param \Zend\Config\Config $config
     */
    public function __construct (\Zend\Config\Config $config)
    {
        $this->_config = $config;
    }


    public function setHandler ()
    {}


    public function start ()
    {
        $this->_initConnection();
        $this->_initBindings();
        $this->_initConsumer();
        
        $this->_loop();
    }


    public function stop ()
    {}
    
    /*
     * Protected/private
     */
    protected function _loop ()
    {
        while (count($this->_channel->callbacks)) {
            $this->_channel->wait();
        }
    }


    /**
     * Initializes the connection and channel objects.
     */
    protected function _initConnection ()
    {
        $config = $this->_config->connection;
        
        $this->_conn = new AMQPConnection($config->host, $config->port, $config->user, $config->password, $config->vhost);
        $this->_channel = $this->_conn->channel();
    }


    /**
     * Initializes the exchange and the queue and binds them.
     */
    protected function _initBindings ()
    {
        $exchangeName = $this->_declareExchange();
        $queueName = $this->_declareQueue();
        
        $this->_channel->queue_bind($queueName, $exchangeName);
    }


    protected function _initConsumer ()
    {
        $config = $this->_config->bindings->consumer;
        $this->_channel->basic_consume($this->_getRequestQueueName(), $config->tag, $config->noLocal, $config->noAck, $config->exclusive, $config->noWait, $this->_getCallback());
    }


    /**
     * Initializes the exchange.
     */
    protected function _declareExchange ()
    {
        $config = $this->_config->bindings->exchange;
        $opts = $config->options;
        $this->_channel->exchange_declare($config->name, $opts->type, $opts->passive, $opts->durable, $opts->autoDelete);
        
        return $config->name;
    }


    /**
     * Initializes the queue.
     */
    protected function _declareQueue ()
    {
        $config = $this->_config->bindings->queue;
        $opts = $config->options;
        $this->_channel->queue_declare($config->name, $opts->passive, $opts->durable, $opts->exclusive, $opts->autoDelete);
        
        return $config->name;
    }


    protected function _getRequestQueueName ()
    {
        return $this->_config->bindings->queue->name;
    }


    protected function _getCallback ()
    {
        return function($msg) {
            _dump($msg);
        };
    }
}