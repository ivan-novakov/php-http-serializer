<?php

define('HTTPSER_DIR', dirname(__FILE__) . '/');

require 'vendor/autoload.php';

//---------------
function _dump ($value)
{
    error_log(print_r($value, true));
}


