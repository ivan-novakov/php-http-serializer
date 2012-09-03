<?php

$ch = curl_init();
//curl_setopt($ch, CURLOPT_URL, "https://mcuc.cesnet.cz/RPC2");
curl_setopt($ch, CURLOPT_URL, "https://hroch.cesnet.cz/test/target.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$output = curl_exec($ch);

if (curl_errno($ch)) {
    die(curl_error($ch));
}

echo $output;

$info = curl_getinfo($ch);
print_r($info);