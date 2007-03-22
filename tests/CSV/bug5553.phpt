--TEST--
File_CSV Test Case bug5553: Write a file with delimiter inside quotes and don't pass the quote option
--FILE--
<?php
// $Id$
/**
 * Write a file with delimiter inside quotes and don't pass the quote option
 */
require_once 'File/CSV.php';

$conf = array();
$data = array();

$conf['fields'] = 3;
$conf['sep']    = ',';
$conf['quote']  = '"';

$data[] = 'Hi';
$data[] = 'Hello';
$data[] = 'Hi,world';

$file = 'bug5553.csv';
$res = File_CSV::write($file, $data, $conf);

echo "Write:\n";
var_dump($res);
echo "\n";

$read = array();
while ($res = File_CSV::read($file, $conf)) {
    $read[] = $res;
}

echo "Data:\n";
print_r($read);
echo "\n";

unlink(bug5553.csv)
?>
--EXPECT--
Write:
bool(true)

Data:
Array
(
    [0] => Array
        (
            [0] => Hi
            [1] => Hello
            [2] => Hi,world
        )

)