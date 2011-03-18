--TEST--
File_CSV Test Case bug5257: Delimiter problem if first field is empty
--FILE--
<?php
// $Id$
/**
 * Test for:
 * - File_CSV::readQuoted()
 */

require_once 'File/CSV.php';

$file = dirname(__FILE__) . '/bug5257.csv';
$conf = File_CSV::discoverFormat($file);

print "Format:\n";
print_r(File_CSV::read($file, $conf));
print "\n";
print_r(File_CSV::read($file, $conf));
print "\n";
print_r(File_CSV::read($file, $conf));
print "\n";
print_r(File_CSV::read($file, $conf));
print "\n";
?>
--EXPECT--
Format:
Array
(
    [0] => 
    [1] => foo
)

Array
(
    [0] => foo
    [1] => 
)

Array
(
    [0] => 
    [1] => foo
)

Array
(
    [0] => foo
    [1] => 
)
