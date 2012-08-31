<?php

namespace HttpSer\Log;
use HttpSer\Observer;


class Log implements Observer\ObserverInterface
{
    
    /**
     * Logger object.
     * 
     * @var \Zend\Log\Logger
     */
    protected $_logger = NULL;


    public function __construct (\Zend\Log\Logger $logger)
    {
        $this->_logger = $logger;
    }


    public function update ($message)
    {
        $this->_logger->debug($message);
    }


    public function getIdent ()
    {
        return 'debug';
    }
}