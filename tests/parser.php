<?php
$path = ini_get('include_path');
ini_set('include_path', realpath('../') . ":$path");
require_once 'File/CSV.php';
$conf = array(
    'fields' => 4,
    'sep'    => "\t",
    'quote'  => '"',
    'header' => true
);
ob_implicit_flush(true);
$argv = $_SERVER['argv'];
$file = $argv[1];
PEAR::setErrorHandling(PEAR_ERROR_PRINT, "warning: %s\n");
$i = 0;
while ($fields = File_CSV::read($file, $conf)) {
    print_r($fields);
    //if ($i++ >= 2) break;
}
echo "\n"

?>