--TEST--
File_Util::listDir() with sorting
--FILE--
<?php
// $Id: $
require_once 'File/Util.php';

$dir = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'listDir';
$dirs = File_Util::listDir($dir, FILE_LIST_ALL &~ FILE_LIST_DOTS, FILE_SORT_REVERSE | FILE_SORT_DATE);
print_r($dirs);
?>
--EXPECT--
Array
(
    [0] => stdClass Object
        (
            [name] => dir
            [size] => 
            [date] => 1299952130
        )

    [1] => stdClass Object
        (
            [name] => test.csv
            [size] => 537
            [date] => 1299952123
        )

    [2] => stdClass Object
        (
            [name] => bug14030-to-delete.csv
            [size] => 161
            [date] => 1299952114
        )

    [3] => stdClass Object
        (
            [name] => parser.php
            [size] => 577
            [date] => 1299952107
        )

)
