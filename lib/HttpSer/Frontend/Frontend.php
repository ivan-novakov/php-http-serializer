<?php
namespace HttpSer\Frontend;
use HttpSer\Agent;
use HttpSer\Util;


class Frontend
{

    /**
     * Configuration object.
     * 
     * @var \Zend\Config\Config
     */
    protected $_config = NULL;

    /**
     * Logger object.
     * 
     * @var \Zend\Log\Logger
     */
    protected $_logger = NULL;

    /**
     * Serializer adapter.
     * 
     * @var \Zend\Serializer\Adapter\AdapterInterface
     */
    protected $_serializer = NULL;

    /**
     * Unique identification of the instance.
     * 
     * @var string
     */
    protected $_ident = NULL;

    /**
     * Array for storing timer data.
     * 
     * @var array
     */
    protected $_timers = array();


    /**
     * Constructor.
     * 
     * @param array $config
     */
    public function __construct (Array $config = array())
    {
        $this->_ident = uniqid();
        $this->_config = new \Zend\Config\Config($config);
        $this->_logger = $this->_initLogger();
        $this->_serializer = $this->_initSerializer();
        $this->_timer = new Util\Timer();
    }


    /**
     * Run the frontend.
     */
    public function run ()
    {
        $this->_timer->startTimer('total');
        
        $request = new \Zend\Http\PhpEnvironment\Request();
        $this->_log(sprintf("Processing '%s' request...", $request->getMethod()));
        
        $agent = new Agent\Agent($this->_config->agent);
        $agent->setLogger($this->_logger);
        
        try {
            $this->_timer->startTimer('connect');
            
            $agent->connect();
            
            $this->_timer->stopTimer('connect');
            $this->_log(sprintf("Connected to queue [%f s]", $this->_timer->getTimerTime('connect')));
        } catch (\Exception $e) {
            $this->_handleException($e, 'Connecting to queue');
        }
        
        try {
            $this->_timer->startTimer('serialize');
            
            $msgBody = $this->_serializeRequest($request);
            
            $this->_timer->stopTimer('serialize');
            $this->_log(sprintf("Serialized request [%f s]", $this->_timer->getTimerTime('serialize')));
        } catch (\Exception $e) {
            $this->_handleException($e, 'Serializing request');
        }
        
        try {
            $this->_timer->startTimer('request');
            
            $responseData = $agent->sendMessage($msgBody);
            
            $this->_timer->stopTimer('request');
            $this->_log(sprintf("Request dispatched [%f s]", $this->_timer->getTimerTime('request')));
        } catch (\Exception $e) {
            $this->_handleException($e, 'Send message');
        }
        
        try {
            $this->_timer->startTimer('unserialize');
            
            $response = $this->_unserializeResponse($responseData);
            
            $this->_timer->stopTimer('unserialize');
            $this->_log(sprintf("Response unserialized [%f s]", $this->_timer->getTimerTime('unserialize')));
        } catch (\Exception $e) {
            $this->_handleException($e, 'Unserializing response');
        }
        
        $this->_returnResponse($response);
    }
    
    /*
     * Private/protected
     */
    
    /**
     * Handles an exception.
     * 
     * @param \Exception $e
     * @param string $label
     */
    protected function _handleException (\Exception $e, $label = 'Exception')
    {
        $this->_log(sprintf("%s: [%s] %s", $label, get_class($e), $e->getMessage()), \Zend\Log\Logger::ERR);
        $this->_returnErrorResponse();
    }


    /**
     * Sends an error HTTP response.
     */
    protected function _returnErrorResponse ()
    {
        $response = new \Zend\Http\PhpEnvironment\Response();
        $response->setStatusCode(500);
        
        $response->send();
        $this->_exit();
    }


    /**
     * Sends a valid HTTP response.
     * 
     * @param \Zend\Http\Response $response
     */
    protected function _returnResponse (\Zend\Http\Response $response)
    {
        $currentResponse = new \Zend\Http\PhpEnvironment\Response();
        
        $currentResponse->setStatusCode($response->getStatusCode());
        $currentResponse->setHeaders($response->getHeaders());
        $currentResponse->setContent($response->getContent());
        
        $this->_log(sprintf("Returning response: %d byte(s)", strlen($currentResponse->getContent())));
        $currentResponse->send();
        $this->_exit();
    }


    /**
     * Initalizes and returns the logge object.
     * 
     * @return \Zend\Log\Logger
     */
    protected function _initLogger ()
    {
        $loggerConfig = $this->_config->logger;
        
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($loggerConfig->writer, null, $loggerConfig->options->toArray());
        
        \Zend\Log\Logger::registerErrorHandler($logger);
        \Zend\Log\Logger::registerExceptionHandler($logger);
        
        return $logger;
    }


    /**
     * Initializes and returns the serializer adapter.
     * 
     * @return \Zend\Serializer\Adapter\AdapterInterface
     */
    protected function _initSerializer ()
    {
        $serializerConfig = $this->_config->serializer;
        return \Zend\Serializer\Serializer::factory($serializerConfig->adapter, $serializerConfig->options);
    }


    /**
     * Serializes the HTTP request object.
     * 
     * @param \Zend\Http\Request $request
     * @return string
     */
    protected function _serializeRequest (\Zend\Http\Request $request)
    {
        return $this->_serializer->serialize($request);
    }


    /**
     * Unserializes the HTTP response object.
     * 
     * @param string $responseData
     * @return \Zend\Http\Response
     */
    protected function _unserializeResponse ($responseData)
    {
        $response = $this->_serializer->unserialize($responseData);
        if (! ($response instanceof \Zend\Http\Response)) {
            throw new Exception\InvalidResponseException();
        }
        
        return $response;
    }


    protected function _log ($message, $priority = \Zend\Log\Logger::INFO)
    {
        $this->_logger->log($priority, sprintf("FRONTEND [%s/%s]: %s", $_SERVER['REMOTE_ADDR'], $this->_ident, $message));
    }


    protected function _exit ()
    {
        $this->_timer->stopTimer('total');
        $this->_log(sprintf("Complete [%f s]", $this->_timer->getTimerTime('total')));
        exit();
    }
}