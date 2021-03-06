--TEST--
File_CSV Test Case 029: Output double quotes.
--FILE--
<?php
// $Id$
/**
 * Test for:
 *  - Extreme usage of double quotes
 */

require_once 'File/CSV.php';

$file = dirname(__FILE__) . '/029.csv';
$conf = array(
    'fields' => 1,
    'quote' => '"'
);

$data = array();
while ($res = File_CSV::read($file, $conf)) {
    $data[] = $res;
}

print "Data:\n";
print_r($data);
print "\n";
?>
--EXPECT--
Data:
Array
(
    [0] => Array
        (
            [0] => Joan ""the bone"" Anne
        )

)
