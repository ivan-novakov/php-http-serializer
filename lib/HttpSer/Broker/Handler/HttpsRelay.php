<?php
namespace HttpSer\Broker\Handler;


class HttpsRelay extends AbstractHandler
{

    /**
     * HTTP client object.
     *
     * @var \Zend\Http\Client
     */
    protected $_client = NULL;


    public function process ($data)
    {
        $request = unserialize($data);
        if (FALSE === $request || ! ($request instanceof \Zend\Http\Request)) {
            // error
        }
        
        $request->setUri($this->_config->targetUrl);
        $request->getHeaders()
            ->addHeaders(array(
            'Connection' => 'keep-alive'
        ));
        
        $client = $this->getClient();
 
        $response = $client->send($request);

        return serialize($response);
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
        
        return $this->_client;
    }
}