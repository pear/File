<?php
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002 Tomas Von Veschler Cox                            |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Tomas V.V.Cox <cox@idecnet.com>                             |
// |                                                                      |
// +----------------------------------------------------------------------+
//
// $Id$

require_once 'PEAR.php';
require_once 'File.php';

/**
* File class for handling CSV files (Comma Separated Values), a common format
* for exchanging data.
*
* TODO:
*  - Optimization
*  - PHPDoc and Doc
*  - Use getPointer() in discoverFormat
*  - Error correction tests for broken CSV files
*  - Analyze the problem when a field is composed only by a single
*    quoted separator. Ie -> ;";";
*
* Wish:
*  - Support Mac EOL format
*  - Other methods like readAll(), writeAll(), countSomeThing()
*  - Try to detect if a CSV has header or not in discoverFormat()
*
* @author Tomas V.V.Cox <cox@idecnet.com>
*/
class File_CSV
{
    /**
    * This raiseError method works in a different way. It will always return
    * false (an error occurred) but it will call PEAR::raiseError() before
    * it. If no default PEAR global handler is set, will trigger an error.
    *
    * @param string $error The error message
    * @return bool always false
    */
    function raiseError($error)
    {
        // If a default PEAR Error handler is not set trigger the error
        // XXX Add a PEAR::isSetHandler() method?
        if ($GLOBALS['_PEAR_default_error_mode'] == PEAR_ERROR_RETURN) {
            PEAR::raiseError($error, null, PEAR_ERROR_TRIGGER, E_USER_WARNING);
        } else {
            PEAR::raiseError($error);
        }
        return false;
    }

    function _conf(&$conf, &$error)
    {
        // check conf
        if (!is_array($conf)) {
            return $error = "Invalid configuration";
        }
        if (isset($conf['sep'])) {
            if (strlen($conf['sep']) != 1) {
                return $error = 'Separator can only be one char';
            }
        } else {
            return $error = 'Missing separator (the "sep" key)';
        }
        if (!isset($conf['fields']) || !is_numeric($conf['fields'])) {
            return $error = 'The number of fields must be numeric (the "fields" key)';
        }
        if (isset($conf['quote'])) {
            if (strlen($conf['quote']) != 1) {
                return $error = 'The quote char must be one char (the "quote" key)';
            }
        } else {
            $conf['quote'] = null;
        }
        if (!isset($conf['crlf'])) {
            $conf['crlf'] = "\n";
        }
    }

    function getPointer($file, &$conf, $mode = FILE_MODE_READ)
    {
        static $resources  = array();
        static $config;
        if (isset($resources[$file])) {
            $conf = $config;
            return $resources[$file];
        }
        File_CSV::_conf($conf, $error);
        if ($error) {
            return File_CSV::raiseError($error);
        }
        $config = $conf;
        PEAR::pushErrorHandling(PEAR_ERROR_RETURN);
        $fp = &File::_getFilePointer($file, $mode);
        PEAR::popErrorHandling();
        if (PEAR::isError($fp)) {
            return File_CSV::raiseError($fp);
        }
        $resources[$file] = $fp;

        if ($mode == FILE_MODE_READ && !empty($conf['header'])) {
            if (!File_CSV::read($file, $conf)) {
                return false;
            }
        }
        return $fp;
    }

    function unquote($field, $quote)
    {
        // Incase null fields (form: ;;)
        if (!strlen($field)) {
            return $field;
        }
        if ($quote && $field{0} == $quote && $field{strlen($field)-1} == $quote) {
            return substr($field, 1, -1);
        }
        return $field;
    }

    function read($file, &$conf)
    {
        if (!$fp = File_CSV::getPointer($file, $conf, FILE_MODE_READ)) {
            return false;
        }
        $buff = $c = null;
        $ret  = array();
        $i = 1;
        $in_quote = false;
        $quote = $conf['quote'];
        while (($i <= $conf['fields']) && (($ch = fgetc($fp)) !== false)) {
            $prev = $c;
            $c = $ch;
            if ($quote && $c == $quote &&
                ($prev == $conf['sep'] || $prev == "\n" || $prev === null))
            {
                $in_quote = true;
            }
            if ($in_quote && $c == $conf['sep'] && $prev == $conf['quote']) {
                $in_quote = false;
            }
            if (!$in_quote && $c == $conf['sep']) {
                $ret[] = File_CSV::unquote($buff, $quote);
                $buff = '';
                $i++;
                continue;
            }
            if ($c == "\n") {
                $sub = ($prev == "\r") ? 2 : 1;
                if ((strlen($buff) >= $sub) && ($buff{strlen($buff) - $sub} == $quote)) {
                    $in_quote = false;
                }
                if (!$in_quote) {
                    if ($prev == "\r") {
                        $buff = substr($buff, 0, -1);
                    }
                    $ret[] = File_CSV::unquote($buff, $quote);
                    $buff = '';
                    $i++;
                    continue;
                }
            }
            $buff .= $c;
        }
        if ($buff) {
            $ret[] = File_CSV::unquote($buff, $quote);
        }
        return !feof($fp) ? $ret : false;
    }

    function write($file, $fields, &$conf)
    {
        if (!$fp = File_CSV::getPointer($file, $conf, FILE_MODE_WRITE)) {
            return false;
        }
        if (count($fields) != $conf['fields']) {
            File_CSV::raiseError("Wrong fields number count: '". count($fields) .
                                  "' expected ".$conf['fields']);
            return true;
        }
        $write = '';
        for ($i = 0; $i < count($fields); $i++) {
            if (!is_numeric($fields[$i]) && $conf['quote']) {
                $write .= $conf['quote'] . $fields[$i] . $conf['quote'];
            } else {
                $write .= $fields[$i];
            }
            if ($i < (count($fields) - 1)) {
                $write .= $conf['sep'];
            } else {
                $write .= $conf['crlf'];
            }
        }
        if (!fwrite($fp, $write)) {
            return File_CSV::raiseError('Can not write to file');
        }
        return true;
    }

    /**
    * Discover the format of a CSV file (the number of fields, the separator
    * and if it quote string fields)
    *
    * @param string the CSV file name
    * @return mixed Assoc array or false
    */
    function discoverFormat($file)
    {
        if (!$fp = @fopen($file, 'r')) {
            return File_CSV::raiseError("Could not open file: $file");
        }
        $seps = array("\t", ';', ':', ',');
        $matches = array();
        // Take the first 10 lines and store the number of ocurrences
        // for each separator in each line
        for ($i = 0; ($i < 10) && ($line = fgets($fp, 4096)); $i++) {
            foreach ($seps as $sep) {
                $matches[$sep][$i] = substr_count($line, $sep);
            }
        }
        $final = array();
        // Order the results by amount of equal ocurrences
        foreach ($matches as $sep => $res) {
            $times = array();
            $times[0] = 0;
            foreach ($res as $k => $num) {
                if ($num > 0) {
                    $times[$num] = (isset($times[$num])) ? $times[$num] + 1 : 1;
                }
            }
            arsort($times);
            $fields[$sep] = key($times);
            $amount[$sep] = $times[key($times)];
        }
        arsort($amount);
        $sep    = key($amount);
        $fields = $fields[$sep];
        if (empty($fields)) {
            return File_CSV::raiseError('Could not discover the separator');
        }
        $conf['fields'] = $fields + 1;
        $conf['sep']    = $sep;
        // Test if there are fields with quotes arround in the first 5 lines
        $quotes = '"\'';
        $quote  = null;
        rewind($fp);
        for ($i = 0; ($i < 5) && ($line = fgets($fp, 4096)); $i++) {
            if (preg_match("|$sep([$quotes]).*([$quotes])$sep|U", $line, $match)) {
                if ($match[1] == $match[2]) {
                    $quote = $match[1];
                    break;
                }
            }
            if (preg_match("|^([$quotes]).*([$quotes])$sep|", $line, $match)
                || preg_match("|([$quotes]).*([$quotes])$sep\s$|Us", $line, $match))
            {
                if ($match[1] == $match[2]) {
                    $quote = $match[1];
                    break;
                }
            }
        }
        $conf['quote'] = $quote;
        fclose($fp);
        // XXX What about trying to discover the "header"?
        return $conf;
    }
}
?>