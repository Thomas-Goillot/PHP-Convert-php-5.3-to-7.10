<?php 
include 'config.php';
include 'logColor.php';
include 'convert.php';

$convert = new Convert("", $tempFolder, $returnFolder, TRUE);

//Clear all working folders
$convert->clearFolder($returnFolder);
$convert->clearFolder($tempFolder);





?>