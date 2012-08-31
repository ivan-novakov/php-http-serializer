<?php

namespace HttpSer\Observer;


interface ObserverInterface
{


    public function getIdent ();


    public function update ($message);
}