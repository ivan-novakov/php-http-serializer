<?php
namespace HttpSer\Agent\Exception;


class ResponseTimeoutException extends \Exception
{


    public function __construct ($timeout)
    {
        parent::__construct(sprintf("Response timeout %d second(s) exceeded", $timeout));
    }
}