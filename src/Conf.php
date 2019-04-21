<?php
namespace CompressImg;
/**
 * type description here.
 * @author: david
 * @copyright: compressImg
 * Created: 2019-04-21 15:46
 * $Id$
 */

class Conf{
    private $vars = [];

    public function __construct()
    {
        $this->vars = [
            'ImgCheckLib' => 0,
            'CompressQuality' => 50,
            'ImgOptDebug' => 0,
            'LogFile' => $_SERVER['DOCUMENT_ROOT'] . '/Temp/Logs/CompressImg.log',

        ];
        //check dir exist, else create
        $logDir = dirname($this->vars['LogFile']);
        if (is_dir($logDir) && !is_writable($logDir)){
            mkdir($logDir, 0755, true);
        }
        return $this->vars;
    }
}