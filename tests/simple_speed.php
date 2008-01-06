<?php
/* There's nothing scientific about this but it does
 * catch it when we messing up badly speed wise, talking about
 * couple of second speed decrease
 */
require_once 'File/CSV.php';
$file = '100000-rows__2-columns.csv';
$conf = File_CSV::discoverFormat($file);

$_start_time = time();

while ($row = File_CSV::read($file, $conf)) {
    // some random process
    $foo = substr($row[0] ,0 , 1);
}

echo "\n" . 'Processing time: ', time()-$_start_time, ' seconds.';