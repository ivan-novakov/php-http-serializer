<?php
namespace HttpSer\Agent;
use PhpAmqpLib\Message\AMQPMessage;
use HttpSer\Observer;
use HttpSer\Queue;


class Agent implements \Zend\Log\LoggerAwareInterface
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
     * The name (identification) of the response queue.
     * 
     * @var string
     */
    protected $_responseQueueName = '';

    /**
     * Response message.
     * 
     * @var AMQPMessage
     */
    protected $_response = NULL;

    /**
     * If set to true, the agent is currently waiting for response.
     * 
     * @var boolean
     */
    protected $_responseReceived = false;

    protected $_startWaitTime = 0;


    /**
     * Constructor.
     *
     * @param \Zend\Config\Config $config
     */
    public function __construct (\Zend\Config\Config $config)
    {
        $this->_config = $config;
    }


    /**
     * Destructor.
     */
    public function __destruct ()
    {
        $this->disconnect();
    }


    /**
     * Connects the agent to the queue broker.
     */
    public function connect ()
    {
        $this->_channel = new Queue\Channel($this->_config->connection);
        
        $queueName = $this->_getResponseQueueName(true);
        
        $this->_channel->queueDeclare($queueName, $this->_config->bindings->queue->options);
        $this->_channel->queueBind($queueName, $this->_getExchangeName(), $queueName);
        $this->_channel->basicConsume($this->_getResponseQueueName(), $this->_config->bindings->consumer, $this->_getCallback());
    }


    /**
     * Disconnects the agent from the queue broker.
     */
    public function disconnect ()
    {
        if ($this->isConnected()) {
            $this->_channel->disconnect();
        }
    }


    /**
     * Returns true if the channel has been initialized.
     * 
     * @return boolean
     */
    public function isConnected ()
    {
        return ($this->_channel instanceof Queue\Channel);
    }


    /**
     * Sends a message to the broker.
     * 
     * @param mixed $message
     * @param boolean $returnRawResponse
     * @return mixed
     */
    public function sendMessage ($message, $returnRawResponse = false)
    {
        $correlationId = $this->_generateCorrelationId();
        
        $msg = new AMQPMessage($message, array(
            'content_type' => 'text/plain', 
            'delivery_mode' => 2, 
            'correlation_id' => $correlationId, 
            'reply_to' => $this->_getResponseQueueName()
        ));
        
        $this->_channel->basicPublish($msg, $this->_getExchangeName());
        
        $this->_resetResponseReceived();
        $this->_resetTimeLimit();
        
        while ($this->_channel->getCallbackCount()) {
            if ($this->_isResponseReceived()) {
                return $this->getResponse($returnRawResponse);
            }
            
            if ($this->_isResponseTimeout()) {
                throw new Exception\ResponseTimeoutException($this->_getResponseTimeout());
            }
            
            $this->_channel->waitNonBlocking();
        }
        
        throw new Exception\GeneralException('No response received, no channel callbacks left');
    }


    /**
     * Returns the last response.
     * 
     * @param boolean $rawResponse
     * @return AMQPMessage|NULL
     */
    public function getResponse ($rawResponse = false)
    {
        if ($this->_response) {
            if ($rawResponse) {
                return $this->_response;
            }
            
            return $this->_response->body;
        }
        
        return NULL;
    }


    /**
     * Callback for consuming messages from the response queue.
     * 
     * @param AMQPMessage $msg
     */
    public function consumerCallback (AMQPMessage $msg)
    {
        $this->_response = $msg;
        $this->_setResponseReceived();
    }
    
    /*
     * \Zend\Log\LoggerAwareInterface
     */
    public function setLogger (\Zend\Log\LoggerInterface $logger)
    {}
    
    /*
     * Private/protected
     */
    protected function _resetResponseReceived ()
    {
        $this->_responseReceived = false;
    }


    protected function _isResponseReceived ()
    {
        return $this->_responseReceived;
    }


    protected function _setResponseReceived ()
    {
        $this->_responseReceived = true;
    }


    protected function _resetTimeLimit ()
    {
        $this->_startWaitTime = time();
    }


    protected function _isResponseTimeout ()
    {
        return ((time() - $this->_startWaitTime) > $this->_getResponseTimeout());
    }


    protected function _getResponseTimeout ()
    {
        return $this->_config->get('responseTimeout', 10);
    }


    /**
     * Returns the callback for the response queue consumer.
     * 
     * @return callback
     */
    protected function _getCallback ()
    {
        return array(
            $this, 
            'consumerCallback'
        );
    }


    /**
     * Returns the exchange name.
     * 
     * @return string
     */
    protected function _getExchangeName ()
    {
        return $this->_config->bindings->exchange->name;
    }


    /**
     * Returns the response queue name.
     * 
     * @param boolean $generateIfEmpty
     * @throws Exception\NoResponseQueueException
     * @return string
     */
    protected function _getResponseQueueName ($generateIfEmpty = false)
    {
        if (! $this->_responseQueueName) {
            if (! $generateIfEmpty) {
                throw new Exception\NoResponseQueueException();
            }
            
            $this->_responseQueueName = $this->_generateResponseQueueName();
        }
        
        return $this->_responseQueueName;
    }


    /**
     * Generates a response queue name.
     * 
     * @param string $prefix
     * @return string
     */
    protected function _generateResponseQueueName ($prefix = NULL)
    {
        if (NULL === $prefix) {
            $prefix = $this->_config->bindings->queue->namePrefix;
        }
        
        return uniqid($prefix);
    }


    /**
     * Generates unique correlation ID for an upstream message.
     * 
     * @return string
     */
    protected function _generateCorrelationId ()
    {
        return uniqid('correlation-id-');
    }
}