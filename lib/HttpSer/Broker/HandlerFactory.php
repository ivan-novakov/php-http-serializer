<?php

namespace HttpSer\Broker;


class HandlerFactory
{


    static public function factory ($name, $config)
    {
        if (! class_exists($name)) {
            throw new Exception\UnknownHandlerClassException();
        }
          
        return new $name($config); 
    }
}