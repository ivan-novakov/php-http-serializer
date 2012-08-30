<?php

namespace HttpSer\Broker\Handler;


abstract class AbstractHandler implements HandlerInterface
{

    /**
     * Configuration object.
     * 
     * @var \Zend\Config\Config
     */
    protected $_config = NULL;


    /**
     * Constructor.
     * 
     * @param \Zend\Config\Config $config
     */
    public function __construct (\Zend\Config\Config $config)
    {
        $this->_config = $config;
    }
}