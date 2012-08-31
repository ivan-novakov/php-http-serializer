<?php

namespace HttpSer\Queue;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPConnection;


class Channel
{
    
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


    public function __construct (\Zend\Config\Config $config)
    {
        $this->connect($config);
    }


    public function connect (\Zend\Config\Config $config)
    {
        $this->_conn = new AMQPConnection($config->host, $config->port, $config->user, $config->password, $config->vhost);
        $this->_channel = $this->_conn->channel();
    }


    public function disconnect ()
    {
        $this->_channel->close();
        $this->_conn->close();
    }


    public function exchangeDeclare ($name,\Zend\Config\Config $opts)
    {
        $this->_channel->exchange_declare($name, $opts->type, $opts->passive, $opts->durable, $opts->autoDelete);
    }


    public function queueDeclare ($name, \Zend\Config\Config $opts)
    {
        $this->_channel->queue_declare($name, $opts->passive, $opts->durable, $opts->exclusive, $opts->autoDelete);
    }


    public function queueBind ($queueName, $exchangeName, $routingKey = NULL)
    {
        $this->_channel->queue_bind($queueName, $exchangeName, $routingKey);
    }


    public function basicConsume ($queueName, \Zend\Config\Config $config, $callback)
    {
        $this->_channel->basic_consume($queueName, $config->tag, $config->noLocal, $config->noAck, $config->exclusive, $config->noWait, $callback);
    }


    public function basicPublish (AMQPMessage $msg, $exchangeName, $routingKey = NULL)
    {
        $this->_channel->basic_publish($msg, $exchangeName, $routingKey);
    }


    public function getCallbackCount ()
    {
        return $this->_channel->callbacks;
    }


    public function wait ()
    {
        $this->_channel->wait();
    }
}