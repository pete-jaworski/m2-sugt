<?php
require_once 'vendor/autoload.php';

$logger = new \Appe\Logger();
$db = new \Appe\MSSQL($logger);


$eryk = new \Appe\Controller(
            $db,
            new \Appe\Prestashop(new Curl\Curl, $logger), 
            new \Appe\SubiektGT(new \COM("InsERT.gt"), $db, $logger),
            $logger
        );




//$eryk->getData();
$eryk->uploadData();

