<?php

namespace HttpSer\Broker\Handler;


class Dummy extends AbstractHandler
{


    public function process ($data)
    {
        return '[dummy response: ' . date("c", time()) . ']';
    }
}