<?php

namespace HttpSer\Observer;


interface SubjectInterface
{


    public function addObserver (ObserverInterface $observer);

    public function removeObserver(ObserverInterface $observer);

    public function notifyObservers ($message);
}