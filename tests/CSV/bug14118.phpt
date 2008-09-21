--TEST--
File_CSV Test Case bug14118: Error with quoted fields and separators
--FILE--
<?php
// $Id$
require_once 'File/CSV.php';
$file = 'bug14118.csv';
$config = File_CSV::discoverFormat($file);
while ($row = File_CSV::read($file, $config)) {
    print_r($row);
}
?>
--EXPECT--
Array
(
    [0] => ENFB
    [1] => closed
    [2] => Oslo, Fornebu
    [3] => Airport
)
Array
(
    [0] => ENFG
    [1] => medium_airport
    [2] => Leirin, Leirin
    [3] => Airport
)