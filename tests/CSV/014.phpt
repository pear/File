--TEST--
File_CSV Test Case 014: Use 2 modes, first read the file then write to it.
--FILE--
<?php
// $Id$
/**
 * Test for:
 * - parsing, how we handle quotes and separators inside quotes
 *   and empty fields
 * data gotten from http://www.creativyst.com/Doc/Articles/CSV/CSV01.htm
 */

require_once 'File/CSV.php';

$file = '014.csv';
$fileWrite = '014-write.csv';
$conf = File_CSV::discoverFormat($file);

$data = array();
while ($res = File_CSV::read($file, $conf)) {
    $data[] = $res;
}

echo "Data:\n";
print_r($data);
echo "\n";

foreach ($data as $row) {
    $res = File_CSV::write($fileWrite, $row, $conf);
}
echo "Write:\n";
var_dump($res);
unlink($fileWrite);
?>
--EXPECT--
Data:
Array
(
    [0] => Array
        (
            [0] => Foo
            [1] => Bar
            [2] => Foobar
        )

)

Write:
bool(true)