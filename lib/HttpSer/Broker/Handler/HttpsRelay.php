<?php
namespace HttpSer\Broker\Handler;
use HttpSer\Util;


class HttpsRelay extends AbstractHandler
{

    /**
     * HTTP client object.
     *
     * @var \Zend\Http\Client
     */
    protected $_client = NULL;

    /**
     * Serializer object.
     * 
     * @var \Zend\Serializer\Adapter\AdapterInterface
     */
    protected $_serializer = NULL;


    public function __construct (\Zend\Config\Config $config)
    {
        parent::__construct($config);
        
        $serializerConfig = $this->_config->serializer;
        $this->_serializer = \Zend\Serializer\Serializer::factory($serializerConfig->adapter, $serializerConfig->options);
        $this->_timer = new Util\Timer();
    }


    public function process ($data)
    {
        try {
            $request = $this->_unserializeRequest($data);
        } catch (\Exception $e) {
            throw new Exception\UnserializeException();
        }
        
        if (FALSE === $request || ! ($request instanceof \Zend\Http\Request)) {
            throw new Exception\InvalidRequestException();
        }
        
        $request->setUri($this->_config->targetUrl);
        $request->getHeaders()
            ->addHeaders(array(
            'Connection' => 'keep-alive'
        ));
        
        $client = $this->getClient();

        $this->_timer->startTimer('request');
        $response = $client->send($request);
        $this->_timer->stopTimer('request');

        try {
            $responseData = $this->_serializeResponse($response);
        } catch (\Exception $e) {
            throw new Exception\SerializeException();
        }
        
        return $responseData;
    }


    /**
     * Sets the HTTP client object.
     *
     * @param \Zend\Http\Client $client            
     */
    public function setClient (\Zend\Http\Client $client)
    {
        $this->_client = $client;
    }


    /**
     * Returns the HTTP client object
     *
     * @return \Zend\Http\Client
     */
    public function getClient ()
    {
        if (! $this->_client) {
            $clientConfig = $this->_config->client;
            $client = new \Zend\Http\Client(NULL, $clientConfig->options);
            
            $adapterConfig = $clientConfig->adapter;
            $adapterClass = $adapterConfig->class;
            
            $adapter = new $adapterClass();
            $adapter->setOptions($adapterConfig->options);
            $adapter->setStreamContext($adapterConfig->streamContext->toArray());
            
            $client->setAdapter($adapter);
            
            $this->setClient($client);
        }
        //_dump(stream_context_get_params($this->_client->getAdapter()->getStreamContext()));
        return $this->_client;
    }
    
    /*
     * Protected/private
     */
    
    /**
     * Returns the unserialized request data.
     * 
     * @param string $responseData
     * @return \Zend\Http\Response
     */
    protected function _unserializeRequest ($requestData)
    {
        return $this->_serializer->unserialize($requestData);
    }


    /**
     * Returns the serialized response data.
     * 
     * @param \Zend\Http\Response $response
     * @return string
     */
    protected function _serializeResponse (\Zend\Http\Response $response)
    {
        return $this->_serializer->serialize($response);
    }
}