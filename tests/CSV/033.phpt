--TEST--
File_CSV Test Case 033: Double quoting madness
--FILE--
<?php
// $id: $

require_once 'File/CSV.php';

$file = dirname(__FILE__) . '/032.csv';
$conf = File_CSV::discoverFormat($file);

echo "Format:\n";
print_r($conf);
echo "\n";

$data = array();
while ($res = File_CSV::read($file, $conf)) {
    $data[] = $res;
}

echo "Data:\n";
print_r($data);
echo "\n";
?>
--EXPECT--
Format:
Array
(
    [fields] => 5
    [sep] => ,
    [quote] => "
)

Data:
Array
(
    [0] => Array
        (
            [0] => 1
            [1] => 2
            [2] => 3
            [3] => a"b
            [4] => 4
        )
    
    [1] => Array
        (
            [0] => 1
            [1] => 2
            [2] => 
            [3] => 3
            [4] => 
        )
        
    [2] => Array
        (
            [0] => 1
            [1] => 2
            [2] => ,","
            [3] => 4
            [4] => ,
        )

)
