--TEST--
File_CSV Test Case bug11526: Fields count less than expected
--FILE--
<?php
// $Id$
require_once 'File/CSV.php';
$path = dirname(__FILE__) . '/bug11526.csv';
$conf = File_CSV::discoverFormat($path);
echo $conf['fields'];
?>
--EXPECT--
12