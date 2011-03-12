--TEST--
File_Util::realPath() Windows support
--SKIPIF--
<?php
require_once 'File/Util.php';
if (!FILE_WIN32) {
  die('SKIP This test is only for Windows');
}
?>
--FILE--
<?php
// $Id: $
require_once 'File/Util.php';

$drive = substr(getcwd(),0, 2);
var_dump((($drive . '/a/weird/path/is' === File_Util::realpath('/a\\weird//path\is/that/./../', '/')) ? true : false));
var_dump((($drive . '/a/weird/path/is/that' == File_Util::realpath('/a\\weird//path\is/that/./../that/.', '/')) ? true : false));
var_dump((($drive . '\windows/\system32' == File_Util::realpath('/windows/system32')) ? true : false));
?>
--EXPECT--
bool(true)
bool(true)
bool(true)