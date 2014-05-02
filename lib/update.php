<?php

$path = $_GET["path"];

$path = str_replace("http://api.pigdata.net/plugins/prestashop/update", dirname(__FILE__), $path);

$contents = file_get_contents($path);

header('Content-Type: text/plain; charset=utf-8');

echo $contents;