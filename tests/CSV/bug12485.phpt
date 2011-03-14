--TEST--
File_CSV Test Case bug12485: Triple quotes handled incorrectly
--FILE--
<?php
require_once 'File/CSV.php';
$path = dirname(__FILE__) . '/bug12485.csv';
$conf = File_CSV::discoverFormat($path);
print_r($conf);


echo "\n\n";
while (($tmp = File_CSV::read($path, $conf)) !== false) {
    foreach ($tmp as $t) {
        echo $t . "\n";
    }
}


?>
--EXPECT--
Array
(
    [fields] => 3
    [sep] => ,
    [quote] => "
)


Prius
12"
3
Patrol
34"
8