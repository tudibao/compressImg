<?php
/**
 * CompressImg demo
 *
 * @author: david
 * @copyright: compressImg
 * Created: 2019-04-21 16:21
 * $Id$
 */
namespace CompressImg;

class Demo{
    /**
     * 对图片进行压缩处理
     *
     * @param string @image
     * @param int $c 1启用压缩，0不启用压缩
     * @param string $saveFileame   保存文件名，如果为空，覆盖原文件
     * @return bool true on success else false
     */
    public function __construct($image = '', $c = 1, $saveFileame = '')
    {
        if ($c && in_array(strtolower($image), ['.jpg', '.jpeg', '.png', '.bmp', '.wbmp', '.gif'])) {
            $saveFileame = $saveFileame ?: $image;
            (new ImgCompress($image))
                ->setQuality(50)
                ->setMaxWidth(960)
                ->setCopyMode(false)
                ->compressImg($saveFileame);
        }
        return (file_exists($saveFileame) && filesize($saveFileame) > 0);
    }
}
//
//
//demo code...
//
$imageSrc = 'demo.jpgg';
$compress = new Demo($imageSrc);
echo $compress ? 'success' : 'error';