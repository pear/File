<?php
//* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2003 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Richard Heyes <richard@php.net>                             |
// |          Tal Peer <tal@php.net>                                      |
// +----------------------------------------------------------------------+
// 
// $Id$

/**
 * PEAR::File
 * 
 * @package     File
 * @category    FileSystem
 * @version     $Revision$
 */

/**
 * Requires PEAR
 */
require_once 'PEAR.php';

/**
 * The default number of bytes for reading
 */
if (!defined('FILE_DEFAULT_READSIZE')) {
    define('FILE_DEFAULT_READSIZE', 1024, true);
}

/**
 * The maximum number of bytes for reading lines
 */
if (!defined('FILE_MAX_LINE_READSIZE')) {
    define('FILE_MAX_LINE_READSIZE', 8192000, true);
}

/**
 * Whether file locks should block
 */
if (!defined('FILE_LOCKS_BLOCK')) {
    define('FILE_LOCKS_BLOCK', true, true);
}

/**
 * Mode to use for reading from files
 */
define('FILE_MODE_READ', 'rb', true);

/**
 * Mode to use for truncating files, then writing
 */
define('FILE_MODE_WRITE', 'wb', true);

/**
 * Mode to use for appending to files
 */
define('FILE_MODE_APPEND', 'ab', true);

/**
 * Use this when a shared (read) lock is required
 */
define('FILE_LOCK_SHARED', LOCK_SH | (FILE_LOCKS_BLOCK ? 0 : LOCK_NB), true);

/**
 * Use this when an exclusive (write) lock is required
 */
define('FILE_LOCK_EXCLUSIVE', LOCK_EX | (FILE_LOCKS_BLOCK ? 0 : LOCK_NB), true);

/**
 * Class for handling files
 * 
 * A class with common functions for writing,
 * reading and handling files and directories
 * 
 * @author  Richard Heyes <richard@php.net>
 * @author  Tal Peer <tal@php.net>
 * @author  Michael Wallner <mike@php.net>
 * @access  public 
 * @package File
 */
class File extends PEAR 
{
    /**
     * Destructor
     * 
     * Unlocks any locked file pointers and closes all filepointers
     * 
     * @access private 
     */
    function _File()
    {
        File::closeAll();
    }
    
    /**
     * Handles file pointers. If a file pointer needs to be opened,
     * it will be. If it already exists (based on filename and mode)
     * then the existing one will be returned.
     * 
     * @access  private 
     * @param   string  $filename Filename to be used
     * @param   string  $mode Mode to open the file in
     * @param   mixed   $lock Type of lock to use
     * @return  mixed   PEAR_Error on error or file pointer resource on success
     */
    function &_getFilePointer($filename, $mode, $lock = false)
    {
        $filePointers = &PEAR::getStaticProperty('File', 'filePointers');
        
        // Win32 is case-insensitive
        if (OS_WINDOWS) {
            $filename = strToLower($filename);
        }
        
        // check if file pointer already exists
        if (    !isset($filePointers[$filename][$mode]) || 
                !is_resource($filePointers[$filename][$mode])) {
            
            // check if we can open the file in the desired mode
            switch ($mode)
            {
                case FILE_MODE_READ:
                    if (    !preg_match('/^.+(?<!file):\/\//i', $filename) &&
                            !file_exists($filename)) {
                        return PEAR::raiseError("File does not exist: $filename");
                    }
                break;
                
                case FILE_MODE_APPEND:
                case FILE_MODE_WRITE:
                    if (file_exists($filename)) {
                        if (!is_writable($filename)) {
                            return PEAR::raiseError("File is not writable: $filename");
                        }
                    } elseif (!is_writable($dir = dirname($filename))) {
                        return PEAR::raiseError("Cannot create file in directory: $dir");
                    }
                break;
                
                default:
                    return PEAR::raiseError("Invalid access mode: $mode");
            }
            
            // open file
            $filePointers[$filename][$mode] = @fopen($filename, $mode);
            if (!is_resource($filePointers[$filename][$mode])) {
                return PEAR::raiseError('Failed to open file: ' . $filename);
            }
        }
        
        // lock file
        if ($lock) {
            $lock = $mode == FILE_MODE_READ ? FILE_LOCK_SHARED : FILE_LOCK_EXCLUSIVE;
            $locks = &PEAR::getStaticProperty('File', 'locks');
            if (@flock($filePointers[$filename][$mode], $lock)) {
                $locks[] = &$filePointers[$filename][$mode];
            } elseif (FILE_LOCKS_BLOCK) {
                return PEAR::raiseError("File already locked: $filename");
            } else {
                return PEAR::raiseError("Could not lock file: $filename");
            }
        }
        
        return $filePointers[$filename][$mode];
    } 

    /**
     * Reads an entire file and returns it.
     * Uses file_get_contents if available.
     * 
     * @access  public 
     * @param   string  $filename Name of file to read from
     * @param   mixed   $lock Type of lock to use
     * @return  mixed   PEAR_Error if an error has occured or a string with the contents of the the file
     */
    function readAll($filename, $lock = false)
    {
        if (function_exists('file_get_contents')) {
            if (false === $file = @file_get_contents($filename)) {
                return PEAR::raiseError("Cannot read file: $filename");
            }
            return $file;
        }
        $file = '';
        while (false !== $buf = File::read($filename, FILE_DEFAULT_READSIZE, $lock)) {
            if (PEAR::isError($buf)) {
                return $buf;
            }
            $file .= $buf;
        }
        
        // close the file pointer
        File::close($filename, FILE_MODE_READ);
        
        return $file;
    }

    /**
     * Returns a specified number of bytes of a file. 
     * Defaults to FILE_DEFAULT_READSIZE.  If $size is 0, all file will be read.
     * 
     * @access  public 
     * @param   string  $filename Name of file to read from
     * @param   integer $size Bytes to read
     * @param   mixed   $lock Type of lock to use
     * @return  mixed   PEAR_Error on error or a string which contains the data read
     *                  Will also return false upon EOF
     */
    function read($filename, $size = FILE_DEFAULT_READSIZE, $lock = false)
    {
        static $filePointers;
        
        if (0 == $size) {
            return File::readAll($filename, $lock);
        } 

        if (    !isset($filePointers[$filename]) || 
                !is_resource($filePointers[$filename])) {
            if (PEAR::isError($fp = &File::_getFilePointer($filename, FILE_MODE_READ, $lock))) {
                return $fp;
            } 

            $filePointers[$filename] = &$fp;
        } else {
            $fp = &$filePointers[$filename];
        } 

        return !feof($fp) ? fread($fp, $size) : false;
    } 

    /**
     * Writes the given data to the given filename. 
     * Defaults to no lock, append mode.
     * 
     * @access  public 
     * @param   string  $filename Name of file to write to
     * @param   string  $data Data to write to file
     * @param   string  $mode Mode to open file in
     * @param   mixed   $lock Type of lock to use
     * @return  mixed   PEAR_Error on error or number of bytes written to file.
     */
    function write($filename, $data, $mode = FILE_MODE_APPEND, $lock = false)
    {
        if (PEAR::isError($fp = &File::_getFilePointer($filename, $mode, $lock))) {
            return $fp;
        }
        if (-1 === $bytes = @fwrite($fp, $data, strlen($data))) {
            return PEAR::raiseError("Cannot write data: '$data' to file: '$filename'");
        }
        return $bytes;
    } 

    /**
     * Reads and returns a single character from given filename
     * 
     * @access  public 
     * @param   string  $filename Name of file to read from
     * @param   mixed   $lock Type of lock to use
     * @return  mixed   PEAR_Error on error or one character of the specified file
     */
    function readChar($filename, $lock = false)
    {
        return File::read($filename, 1, $lock);
    } 

    /**
     * Writes a single character to a file
     * 
     * @access  public 
     * @param   string  $filename Name of file to write to
     * @param   string  $char Character to write
     * @param   string  $mode Mode to use when writing
     * @param   mixed   $lock Type of lock to use
     * @return  mixed   PEAR_Error on error, or 1 on success
     */
    function writeChar($filename, $char, $mode = FILE_MODE_APPEND, $lock = false)
    {
        if (PEAR::isError($fp = &File::_getFilePointer($filename, $mode, $lock))) {
            return $fp;
        }
        if (-1 === @fwrite($fp, $char, 1)) {
            return PEAR::raiseError("Cannot write data: '$data' to file: '$filename'");
        }
        return 1;
    } 

    /**
     * Returns a line of the file (without trailing CRLF).
     * Maximum read line length is FILE_MAX_LINE_READSIZE.
     * 
     * @access  public 
     * @param   string  $filename Name of file to read from
     * @param   boolean $lock Whether file should be locked
     * @return  mixed   PEAR_Error on error or a string containing the line read from file
     */
    function readLine($filename, $lock = false)
    {
        static $filePointers; // Used to prevent unnecessary calls to _getFilePointer()
        
        if (    !isset($filePointers[$filename]) || 
                !is_resource($filePointers[$filename])) {
            if (PEAR::isError($fp = &File::_getFilePointer($filename, FILE_MODE_READ, $lock))) {
                return $fp;
            } 

            $filePointers[$filename] = &$fp;
        } else {
            $fp = &$filePointers[$filename];
        } 

        if (feof($fp)) {
            return false;
        } 
        
        return rtrim(fgets($fp, FILE_MAX_LINE_READSIZE), "\r\n");
    } 

    /**
     * Writes a single line, appending a LF (by default)
     * 
     * @access  public 
     * @param   string  $filename Name of file to write to
     * @param   string  $line Line of data to be written to file
     * @param   string  $mode Write mode, can be either FILE_MODE_WRITE or FILE_MODE_APPEND
     * @param   string  $crlf The CRLF your system is using. UNIX = \n Windows = \r\n Mac = \r
     * @param   mixed   $lock Whether to lock the file
     * @return  mixed   PEAR_Error on error or number of bytes written to file (including appended crlf)
     */
    function writeLine($filename, $line, $mode = FILE_MODE_APPEND, $crlf = "\n", $lock = false)
    {
        if (PEAR::isError($fp = &File::_getFilePointer($filename, $mode, $lock))) {
            return $fp;
        }
        if (-1 === $bytes = fwrite($fp, $line . $crlf)) {
            return PEAR::raiseError("Cannot write data: '$data' to file: '$file'");
        }
        return $bytes;
    } 

    /**
     * This rewinds a filepointer to the start of a file
     * 
     * @access  public 
     * @param   string  $filename The filename
     * @param   string  $mode Mode the file was opened in
     * @return  mixed   PEAR Error on error, true on success
     */
    function rewind($filename, $mode)
    {
        if (PEAR::isError($fp = &File::_getFilePointer($filename, $mode))) {
            return $fp;
        }
        if (!@rewind($fp)) {
            return PEAR::raiseError("Cannot rewind file: $filename");
        }
        return true;
    } 

    /**
     * Closes all open file pointers
     * 
     * @access  public
     * @return  void
     */
    function closeAll()
    {
        $locks = &PEAR::getStaticProperty('File', 'locks');
        $filePointers = &PEAR::getStaticProperty('File', 'filePointers');
        
        // unlock files
        for ($i = 0, $c = count($locks); $i < $c; $i++) {
            is_resource($locks[$i]) and @flock($locks[$i], LOCK_UN);
        }
        
        // close files
        if (!empty($filePointers)) {
            foreach ($filePointers as $fname => $modes) {
                foreach (array_keys($modes) as $mode) {
                    if (is_resource($filePointers[$fname][$mode])) {
                        @fclose($filePointers[$fname][$mode]);
                    }
                    unset($filePointers[$fname][$mode]);
                }
            }
        }
    }
    
    /**
     * This closes an open file pointer
     * 
     * @access  public 
     * @param   string  $filename The filename that was opened
     * @param   string  $mode Mode the file was opened in
     * @return  mixed   PEAR Error on error, true otherwise
     */
    function close($filename, $mode)
    {
        $filePointers = &PEAR::getStaticProperty('File', 'filePointers');
        
        if (!isset($filePointers[$filename][$mode])) {
            return true;
        }
        
        $fp = $filePointers[$filename][$mode];
        unset($filePointers[$filename][$mode]);
        
        if (is_resource($fp)) {
            // unlock file
            @flock($fp, LOCK_UN);
            // close file
            if (!@fclose($fp)) {
                return PEAR::raiseError("Cannot close file: $filename");
            }
        }
        
        return true;
    } 

    /**
     * This unlocks a locked file pointer.
     * 
     * @access  public 
     * @param   string  $filename The filename that was opened
     * @param   string  $mode Mode the file was opened in
     * @return  mixed   PEAR Error on error, true otherwise
     */
    function unlock($filename, $mode)
    {
        if (PEAR::isError($fp = &File::_getFilePointer($filename, $mode))) {
            return $fp;
        }
        if (!@flock($fp, LOCK_UN)) {
            return PEAR::raiseError("Cacnnot unlock file: $filename");
        }
        return true;
    } 

    /**
     * Returns a string path built from the array $pathParts. Where a join 
     * occurs multiple separators are removed. Joins using the optional 
     * separator, defaulting to the PHP DIRECTORY_SEPARATOR constant.
     * 
     * @access  public 
     * @param   array   $parts Array containing the parts to be joined
     * @param   string  $separator The system directory seperator
     */
    function buildPath($parts, $separator = DIRECTORY_SEPARATOR)
    {
        /*
         * @FIXXME: maybe better use foreach
         */
        
        for ($i = 0, $c = count($parts); $i < $c; $i++) {
            if (    !strlen($parts[$i]) || 
                    preg_match('/^'. preg_quote($separator) .'+$/', $parts[$i])) {
                unset($parts[$i]);
            } elseif (0 == $i) {
                $parts[$i] = rtrim($parts[$i], $separator);
            } elseif ($c - 1 == $i) {
                $parts[$i] = ltrim($parts[$i], $separator);
            } else {
                $parts[$i] = trim($parts[$i], $separator);
            } 
        } 
        return implode($separator, $parts);
    } 

    /**
     * Strips trailing separators from the given path
     * 
     * @deprecated
     * @static
     * @access  public 
     * @param   string $path Path to use
     * @param   string $separator Separator to look for
     * @return  string Resulting path
     */
    function stripTrailingSeparators($path, $separator = DIRECTORY_SEPARATOR)
    {
        return rtrim($path, $separator);
    } 

    /**
     * Strips leading separators from the given path
     * 
     * @deprecated
     * @static
     * @access  public 
     * @param   string $path Path to use
     * @param   string $separator Separator to look for
     * @return  string Resulting path
     */
    function stripLeadingSeparators($path, $separator = DIRECTORY_SEPARATOR)
    {
        return ltrim($path, $separator);
    } 

    /**
     * Returns a path without leading / or C:\. If this is not
     * present the path is returned as is.
     * 
     * @static
     * @access  public 
     * @param   string  $path The path to be processed
     * @return  string  The processed path or the path as is
     */
    function skipRoot($path)
    {
        if (File::isAbsolute($path)) {
            if (OS_WINDOWS) {
                return substr($path, $path{3} == '\\' ? 4 : 3);
            }
            return ltrim($path, '/');
        }
        return $path;
    } 

    /**
     * Returns the temp directory according to either the TMP, TMPDIR, or 
     * TEMP env variables. If these are not set it will also check for the 
     * existence of /tmp, %WINDIR%\temp
     * 
     * @static
     * @access  public 
     * @return  string  The system tmp directory
     */
    function getTempDir()
    {
        if (OS_WINDOWS) {
            if (isset($_ENV['TEMP'])) {
                return $_ENV['TEMP'];
            }
            if (isset($_ENV['TMP'])) {
                return $_ENV['TMP'];
            }
            if (isset($_ENV['windir'])) {
                return $_ENV['windir'] . '\\temp';
            }
            if (isset($_ENV['SystemRoot'])) {
                return $_ENV['SystemRoot'] . '\\temp';
            }
            if (isset($_SERVER['TEMP'])) {
                return $_SERVER['TEMP'];
            }
            if (isset($_SERVER['TMP'])) {
                return $_SERVER['TMP'];
            }
            if (isset($_SERVER['windir'])) {
                return $_SERVER['windir'] . '\\temp';
            }
            if (isset($_SERVER['SystemRoot'])) {
                return $_SERVER['SystemRoot'] . '\\temp';
            }
            return '\temp';
        }
        if (isset($_ENV['TMPDIR'])) {
            return $_ENV['TMPDIR'];
        }
        if (isset($_SERVER['TMPDIR'])) {
            return $_SERVER['TMPDIR'];
        }
        return '/tmp';
    }

    /**
     * Returns a temporary filename using tempnam() and File::getTmpDir().
     * 
     * @access  public 
     * @param   string  $dirname Optional directory name for the tmp file
     * @return  string  Filename and path of the tmp file
     */
    function getTempFile($dirname = null)
    {
        if (!isset($dirname)) {
            $dirname = File::getTempDir();
        } 
        return tempnam($dirname, 'temp.');
    } 

    /**
     * Returns boolean based on whether given path is absolute or not.
     * 
     * @access  public 
     * @param   string  $path Given path
     * @return  boolean True if the path is absolute, false if it is not
     */
    function isAbsolute($path)
    {
        if (preg_match('/\.\./', $path)) {
            return false;
        } 
        if (OS_WINDOWS) {
            return preg_match('/^[a-zA-Z]:(\\\|\/)/', $path);
        }
        return ($path{0} == '/') || ($path{0} == '~');
    } 

    /**
     * Get path relative to another path
     *
     * @static
     * @access  public
     * @return  string
     * @param   string  $path
     * @param   string  $root
     * @param   string  $separator
     */
    function relativePath($path, $root, $separator = DIRECTORY_SEPARATOR)
    {
        $path = File::realpath($path, $separator);
        $root = File::realpath($root, $separator);
        $dirs = explode($separator, $path);
        $comp = explode($separator, $root);
        
        if (OS_WINDOWS) {
            if (strcasecmp($dirs[0], $comp[0])) {
                return $path;
            }
            unset($dirs[0], $comp[0]);
        }
        
        foreach ($comp as $i => $part) {
            if (isset($dirs[$i]) && $part == $dirs[$i]) {
                unset($dirs[$i], $comp[$i]);
            } else {
                break;
            }
        }
         
        return str_repeat('..' . $separator, count($comp)) . implode($separator, $dirs);
    }

    /**
     * Get real path (works with non-existant paths)
     *
     * @static
     * @access  public
     * @return  string
     * @param   string  $path
     * @param   string  $separator
     */
    function realpath($path, $separator = DIRECTORY_SEPARATOR)
    {
        if (!strlen($path)) {
            return $separator;
        }
        
        $drive = '';
        if (OS_WINDOWS) {
            $path = preg_replace('/[\\\\\/]/', $separator, $path);
            if (preg_match('/([a-zA-Z]\:)(.*)/', $path, $matches)) {
                $drive = $matches[1];
                $path  = $matches[2];
            } else {
                $cwd   = getcwd();
                $drive = substr($cwd, 0, 2);
                if ($path{0} !== $separator{0}) {
                    $path  = substr($cwd, 3) . $separator . $path;
                }
            }
        } elseif ($path{0} !== $separator) {
            $cwd  = getcwd();
            $path = $cwd . $separator . File::relativePath($path, $cwd, $separator);
        }
        
        $dirStack = array();
        foreach (explode($separator, $path) as $dir) {
            if (!strlen($dir) || $dir == '.') {
                continue;
            }
            if ($dir == '..') {
                array_pop($dirStack);
            } else {
                $dirStack[] = $dir;
            }
        }
        
        return $drive . $separator . implode($separator, $dirStack);
    }
}

PEAR::registerShutdownFunc(array('File', '_File'));

?>
