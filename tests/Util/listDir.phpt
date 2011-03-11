--TEST--
File_Util Test Case ListDir
--FILE--
<?php
// $Id: $
require_once 'File/Util.php';

$dirs = File_Util::listDir('testDir', FILE_LIST_ALL &~ FILE_LIST_DOTS);
print_r($dirs);


?>
--EXPECT--
