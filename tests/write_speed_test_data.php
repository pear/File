<?php
// Just change this to " or null or whatever you want the delim to be in the csv file
$delim = '"';
$data  = '';
for ($i = 0; $i < 100000; $i++) {
    $data .= $delim . 'foo' . $delim . ',' . $delim . 'bar' . $delim . "\n";
}

file_put_contents('100000-rows__2-columns.csv', $data);
?>