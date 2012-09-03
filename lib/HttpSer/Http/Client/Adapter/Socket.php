<?php
namespace HttpSer\Http\Client\Adapter;


class Socket extends \Zend\Http\Client\Adapter\Socket
{


    public function connect ($host, $port = 80, $secure = false)
    {
        if ($secure && isset($this->connected_to[0])) {
            if (! strstr($host, '://')) {
                $host = $this->config['ssltransport'] . "://" . $host;
            }
        }
        
        parent::connect($host, $port, $secure);
    }
}