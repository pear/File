--TEST--
File_CSV Test Case 013: Reading ="" excel only fields
--FILE--
<?php
// $Id$
/**
 * Test for:
 *   - Reading a ="" excel only field as normal "" field
 */

require_once 'File/CSV.php';

$file = '013.csv';
$conf = File_CSV::discoverFormat($file);
$conf['quote'] = '"';
print "Format:\n";
print_r($conf);
print "\n";

$data = array();
while ($res = File_CSV::read($file, $conf)) {
    $data[] = $res;
}

print "Data:\n";
print_r($data);
print "\n";
?>
--EXPECT--
Format:
Array
(
    [fields] => 3
    [sep] => ,
    [quote] => "
)

Data:
Array
(
    [0] => Array
        (
            [0] => 004343
            [1] =>    Helgi
            [2] =>  Project
multiline
stuff
        )

)
