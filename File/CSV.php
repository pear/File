<?php
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2002 The PHP Group                                |
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

class File_CSV
{
    function unquote($field, $quote)
    {
        if ($quote && $field{0} == $quote && $field{strlen($field)-1} == $quote) {
            return substr($field, 1, -1);
        }
        return $field;
    }

    function read($file, $conf)
    {
        static $resources  = array();
        static $crlf;
        if (!isset($resources[$file])) {
            // check conf
            if (isset($conf['sep'])) {
                if (strlen($conf['sep']) != 1) {
                    return PEAR::raiseError('Separator can only be one char');
                }
            } else {
                return PEAR::raiseError('Missing separator (the "sep" key)');
            }
            if (!isset($conf['fields']) || !is_numeric($conf['fields'])) {
                return PEAR::raiseError('The number of fields must be numeric (the "fields" key)');
            }
            if (isset($conf['quote'])) {
                if (strlen($conf['quote']) != 1) {
                    return PEAR::raiseError('The quote char must be one chat (the "quote" key)');
                }
            } else {
                $conf['quote'] = null;
            }
            $fp = &File::_getFilePointer($file, FILE_MODE_READ);
            if (PEAR::isError($fp)) {
                return $fp;
            }
            $resources[$file] = $fp;
            if (!empty($conf['header'])) {
                File_CSV::read($file, $conf);
            }
        }
        $fp = $resources[$file];
        $buff = null;
        $ret  = array();
        $i = 1;
        $in_quote = false;
        $quote = $conf['quote'];
        while (($i <= $conf['fields']) && (($c = fgetc($fp)) !== false)) {
            $prev = ($buff != '') ? $buff{strlen($buff) - 1} : null;
            if ($quote && $c == $quote &&
                ($prev == $conf['sep'] || $prev == "\n" || $prev === null))
            {
                $in_quote = true;
            }
            if ($quote && $c == $conf['sep'] && $prev == $conf['quote']) {
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
                if ($buff{strlen($buff) - $sub} == $quote) {
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
}
?>