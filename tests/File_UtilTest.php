<?php

require_once 'PHPUnit.php';
require_once 'File/Util.php';

class File_UtilTest extends PHPUnit_TestCase 
{
    function File_UtilTest($name = 'File_UtilTest')
    {
        $this->PHPUnit_TestCase($name);
    } 

    function testlistDir()
    {
        $this->assertEquals(
            array (
              (object)
              array (
                'name' => 'FileTest.php',
                'size' => 8777,
                'date' => 1103281596,
              ),
              (object)
              array (
                'name' => 'File_UtilTest.php',
                'size' => 476,
                'date' => 1105364390,
              ),
              (object)
              array (
                'name' => 'locking.php',
                'size' => 141,
                'date' => 1101900533,
              ),
              (object)
              array (
                'name' => 'parser.php',
                'size' => 640,
                'date' => 1086190037,
              ),
              (object)
              array (
                'name' => 'test',
                'size' => 0,
                'date' => 1101900535,
              ),
              (object)
              array (
                'name' => 'test.csv',
                'size' => 537,
                'date' => 1018873902,
              ),
            ),
            File_Util::listDir('testDir', FILE_LIST_ALL &~ FILE_LIST_DOTS)
        );
    } 

    function testsortFiles()
    {
        $this->assertEquals( array_reverse(
            array (
              (object)
              array (
                'name' => 'test.csv',
                'size' => 537,
                'date' => 1018873902,
              ),
              (object)
              array (
                'name' => 'parser.php',
                'size' => 640,
                'date' => 1086190037,
              ),
              (object)
              array (
                'name' => 'locking.php',
                'size' => 141,
                'date' => 1101900533,
              ),
              (object)
              array (
                'name' => 'test',
                'size' => 0,
                'date' => 1101900535,
              ),
              (object)
              array (
                'name' => 'FileTest.php',
                'size' => 8777,
                'date' => 1103281596,
              ),
              (object)
              array (
                'name' => 'File_UtilTest.php',
                'size' => 476,
                'date' => 1105364390,
              ),
            )),
            File_Util::listDir('testDir', FILE_LIST_ALL &~ FILE_LIST_DOTS, FILE_SORT_REVERSE | FILE_SORT_DATE)
        );
    } 
} 

$result = &PHPUnit::run(new PHPUnit_TestSuite('File_UtilTest'));
echo $result->toString();

?>