<?php
/**
 * test
 *
 * @author: david
 * @copyright: compressImg
 * Created: 2019-04-21 22:49
 * $Id$
 */

require "../vendor/autoload.php";
use CompressImg\CompressImg;

$image = '1.jpg';
$saveFileame = '2.jpg';
(new CompressImg($image))
    ->setQuality(50)
    ->setMaxWidth(960)
    ->setCopyMode(false)
    ->compressImg($saveFileame);

if ((file_exists($saveFileame) && filesize($saveFileame) > 0)){
    echo "success";
}else{
    echo "error";
}