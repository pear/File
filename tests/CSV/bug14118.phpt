--TEST--
File_CSV Test Case bug14118: Error with quoted fields and separators
--FILE--
<?php
// $Id$
require_once 'File/CSV.php';

$path = dirname(__FILE__) . '/bug14118.csv';
$config = File_CSV::discoverFormat($path);
echo 'fields count: ' . $config['fields'] . "\n";
while ($row = File_CSV::read($path, $config)) {
    print_r($row);
}
?>
--EXPECT--
fields count: 5
Array
(
    [0] => ENFB
    [1] => closed
    [2] => Oslo, Fornebu
    [3] => Airport
    [4] => 
)
Array
(
    [0] => ENFG
    [1] => medium_airport
    [2] => Leirin, Leirin
    [3] => Airport
    [4] => 
)
