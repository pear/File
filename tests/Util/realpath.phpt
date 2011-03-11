--TEST--
File_Util::realPath()
--FILE--
<?php
// $Id: $
require_once 'File/Util.php';

$drive = FILE_WIN32 ? substr(getcwd(),0, 2) :'';
var_dump((($drive . '/a/weird/path/is' === File_Util::realpath('/a\\weird//path\is/that/./../', '/')) ? true : false));
var_dump((($drive . '/a/weird/path/is/that' == File_Util::realpath('/a\\weird//path\is/that/./../that/.', '/')) ? true : false));
?>
--EXPECT--
bool(true)
bool(true)