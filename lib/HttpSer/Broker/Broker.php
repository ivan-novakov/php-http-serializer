<?php

namespace HttpSer\Broker;

use \HttpSer\Observer;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPConnection;


class Broker implements Observer\SubjectInterface
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
     * Handler object.
     * 
     * @var Handler\HandlerInterface
     */
    protected $_handler = NULL;
    
    /**
     * An array of observers attached to the object.
     * 
     * @var array
     */
    protected $_observers = array();


    /**
     * Constructor.
     * 
     * @param \Zend\Config\Config $config
     */
    public function __construct (\Zend\Config\Config $config)
    {
        $this->_config = $config;
    }


    public function setHandler (Handler\HandlerInterface $handler)
    {
        $this->_handler = $handler;
    }


    public function start ()
    {
        $this->_debug('Starting broker ...');
        
        $this->_initConnection();
        $this->_initBindings();
        $this->_initConsumer();
        
        $this->_loop();
    }


    public function stop ()
    {}


    public function consumerCallback (AMQPMessage $msg)
    {
        if (! ($this->_handler instanceof Handler\HandlerInterface)) {
            throw new Exception\MissingHandlerException();
        }
        
        $this->_debug(sprintf("Received message:\n%s", $this->_formatPayloadForDebug($msg->body)));
        
        $result = $this->_handler->process($msg->body);
        
        $this->_sendResponse($result, $msg);
    }
    
    /*
     * Observer\SubjectInterface methods
     */
    public function addObserver (Observer\ObserverInterface $observer)
    {
        $this->_observers[$observer->getIdent()] = $observer;
    }


    public function removeObserver (Observer\ObserverInterface $observer)
    {
        if (isset($this->_observers[$observer->getIdent()])) {
            unset($this->_observers[$observer->getIdent()]);
        }
    }


    public function notifyObservers ($message)
    {
        foreach ($this->_observers as $observer) {
            $observer->update($message);
        }
    }
    
    /*
     * Protected/private
     */
    protected function _loop ()
    {
        $this->_debug('Entering loop ...');
        
        while (count($this->_channel->callbacks)) {
            $this->_channel->wait();
        }
    }


    protected function _sendResponse ($response, AMQPMessage $requestMsg)
    {
        $this->_debug(sprintf("Sending response:\n%s", $this->_formatPayloadForDebug($response)));
        
        $responseMsg = new AMQPMessage($response, array(
            'content_type' => 'text/plain', 
            'delivery_mode' => 2, 
            'correlation_id' => $requestMsg->get('correlation_id')
        ));
        
        $this->_channel->basic_publish($responseMsg, $this->_config->bindings->exchange->name, $requestMsg->get('reply_to'));
    }


    /**
     * Initializes the connection and channel objects.
     */
    protected function _initConnection ()
    {
        $config = $this->_config->connection;
        
        $this->_conn = new AMQPConnection($config->host, $config->port, $config->user, $config->password, $config->vhost);
        $this->_channel = $this->_conn->channel();
        
        $this->_debug(sprintf("Initialized connection to %s:%s/%s under user '%s'", $config->host, $config->port, $config->vhost, $config->user));
    }


    /**
     * Initializes the exchange and the queue and binds them.
     */
    protected function _initBindings ()
    {
        $exchangeName = $this->_declareExchange();
        $queueName = $this->_declareQueue();
        
        $this->_channel->queue_bind($queueName, $exchangeName);
        
        $this->_debug(sprintf("Queue '%s' bound to exchange '%s'", $queueName, $exchangeName));
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
        return array(
            $this, 
            'consumerCallback'
        );
    }


    protected function _debug ($message)
    {
        $this->notifyObservers($message);
    }


    protected function _formatPayloadForDebug ($text)
    {
        return sprintf("------\n%s\n-----", $text);
    }
}