--TEST--
File_CSV Test Case bug15233: discoverFormat finds wrong separator
--FILE--
<?php
// $Id: $
/**
 * Test for:
 * - File_CSV::discoverFormat()
 */

require_once 'File/CSV.php';

$file = dirname(__FILE__) . '/bug15233.csv';
$conf = File_CSV::discoverFormat($file);
echo "Format:\n";
print_r($conf);
?>
--EXPECT--
Format:
Array
(
    [fields] => 3
    [sep] => ;
    [quote] => 
)