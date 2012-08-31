<?php

namespace HttpSer\Broker;
use \HttpSer\Observer;
use \HttpSer\Queue;
use PhpAmqpLib\Message\AMQPMessage;


class Broker implements Observer\SubjectInterface
{
    
    /**
     * Configuration object.
     * 
     * @var \Zend\Config\Config
     */
    protected $_config = NULL;
    
    /**
     * Channel object.
     * 
     * @var Queue\Channel;
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
        
        while (count($this->_channel->getCallbackCount())) {
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
        
        $this->_channel->basicPublish($responseMsg, $this->_config->bindings->exchange->name, $requestMsg->get('reply_to'));
    }


    /**
     * Initializes the connection and channel objects.
     */
    protected function _initConnection ()
    {
        $config = $this->_config->connection;
        $this->_channel = new Queue\Channel($config);
        
        $this->_debug(sprintf("Initialized connection to %s:%s/%s under user '%s'", $config->host, $config->port, $config->vhost, $config->user));
    }


    /**
     * Initializes the exchange and the queue and binds them.
     */
    protected function _initBindings ()
    {
        $exchangeConfig = $this->_config->bindings->exchange;
        $this->_channel->exchangeDeclare($exchangeConfig->name, $exchangeConfig->options);
        
        $queueConfig = $this->_config->bindings->queue;
        $this->_channel->queueDeclare($queueConfig->name, $queueConfig->options);
        
        $this->_channel->queueBind($queueConfig->name, $exchangeConfig->name);
        
        $this->_debug(sprintf("Queue '%s' bound to exchange '%s'", $queueConfig->name, $exchangeConfig->name));
    }


    protected function _initConsumer ()
    {
        $config = $this->_config->bindings->consumer;
        $this->_channel->basicConsume($this->_getRequestQueueName(), $config, $this->_getCallback());
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