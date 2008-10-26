<?php
error_reporting(1803);
/* There's nothing scientific about this but it does
 * catch it when we messing up badly speed wise, talking about
 * couple of second speed decrease
 */
require_once 'File/CSV.php';
$file = '100000-rows__2-columns.csv';
//$conf = File_CSV::discoverFormat($file);
$conf = array('fields' => 2, 'sep' => ',', 'quote' => '"');


//Run the test with quotes and without them, NULL that is.

echo "\nRemember to turn off XDebug";
$_start_time = microtime(true);
while ($row = File_CSV::read($file, $conf)) {}

echo "\n" . 'Processing time: ', (microtime(true)-$_start_time), ' seconds.' . "\n";