<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker */
// $Id$
?>
--TEST--
File_CSV Test Case 001: Fields count less than expected
--FILE--
<?php
/**
 * Test for:
 * - File_CSV::discoverFormat()
 * - File_CSV::readQuoted()
 */

require_once '../CSV.php';

$file = '001.csv';
$conf = File_CSV::discoverFormat($file);

print "Format:\n";
print_r($conf);
print "\n";

$data = array();
while ($res = File_CSV::readQuoted($file, $conf)) {
    $data[] = $res;
}

print "Data:\n";
print_r($data);
?>
--EXPECT--
Format:
array
(
    [fields] => 4
    [sep] => ,
    [quote] => "
)

Data:
Array
(
    [0] => Array
        (
            [0] => Field 1-1
            [1] => Field 1-2
            [2] => Field 1-3
            [3] => Field 1-4
        )

    [1] => Array
        (
            [0] => Field 2-1
            [1] => Field 2-2
            [2] => Field 2-3
            [3] =>
        )

    [2] => Array
        (
            [0] => Field 3-1
            [1] => Field 3-2
            [2] =>
            [3] =>
        )

    [3] => Array
        (
            [0] => Field 4-1
            [1] =>
            [2] =>
            [3] =>
        )

)