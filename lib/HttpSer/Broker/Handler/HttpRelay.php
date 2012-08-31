<?php

namespace HttpSer\Broker\Handler;


class HttpRelay extends AbstractHandler
{


    public function process ($data)
    {
        $request = unserialize($data);
        if (FALSE === $request || ! ($request instanceof \Zend\Http\Request)) {
            // error
        }
        
        $client = new \Zend\Http\Client();
        $request->setUri($this->_config->targetUrl);
        $response = $client->send($request);
        
        return serialize($response);
    }
}