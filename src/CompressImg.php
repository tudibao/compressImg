<?php
namespace CompressImg;
/**
 * 图片压缩类：通过缩放来压缩。
 * 如果要保持源图比例，把参数$percent保持为1即可。
 * 即使原比例压缩，也可大幅度缩小。数码相机4M图片。也可以缩为700KB左右。如果缩小比例，则体积会更小。
 *
 * 结果：可保存、可直接显示。
 */
//
//需要的配置项：
//ImgCheckLib   0使用GD库，1检测Imagick，有则使用，没有用GD
//CompressQuality   压缩质量1-100
//ImgOptDebug       是否输出调试信息到自定义日志文件，默认为站点根下的log.html
//
/**
 * Class ImgCompress
 * @package Lib\Classes
 */
class CompressImg{
    /**
     * @var string  源图
     */
    private $src;
    /**
     * @var resource 图片对象
     */
    private $image;
    /**
     * @var array 源图、高信息
     */
    private $imageinfo;
    /**
     * @var float|int   缩放比例
     */
    private $percent = 0.5;
    /**
     * @var bool    是否自动缩放，前提为percent=0
     */
    private $auto_thumb = true;
    /**
     * @var int 触发自动缩放的宽度值，超过此值启用缩放
     */
    private $thumb_threshold = 1100;
    /**
     * @var string 图形库，检测imagick，如果存在，优先使用imagick.没有则使用GD
     */
    private $image_lib = '';
    /**
     * @var int 图片压缩质量，超高，失真越少
     */
    private $compress_quality = 80;
    /**
     * @var int     调试开关，1打开，0关闭
     */
    private $debug = 1;
    /**
     * @var bool 生成图片时，拷贝而不是直接重写
     */
    private $copy_mode = false;
    /**
     * @var string  自定义日志文件
     */
    private $debug_file = '';//'log.html';
    /**
     * @var object configure class instance
     */
    private $conf;
    /**
     * 图片压缩
     * @param string $src 源图
     * @param mixed|int $percent 默认1，压缩比例，当值为0时，启动自动缩放
     * @return void
     */
    public function __construct($src, $percent = 0)
    {
        $this->conf = (new Conf())->getConf();
        $this->src = $src;
        $this->percent = $percent ?: 0;
        $this->debug = $this->conf['ImgOptDebug'];
        $this->debugInfo('check Imagick lib exist?', 'start...', '+-');
        if (class_exists('Imagick') && $this->conf['ImgCheckLib']) {
            $this->image_lib = 'imagick';
        } else {
            $this->image_lib = 'gd';
        }
        $this->compress_quality = $this->conf['CompressQuality'] ?: $this->compress_quality;
        $this->debugInfo('ImgCompress Debug INFO', 'start...', '+-');
        $this->debugInfo('image lib', ['libName' => $this->image_lib, 'compress_quality' => $this->compress_quality]);
    }

    /**
     * 图片缩放方法
     * @param string $src 图片文件名+路径
     * @param mixed|int $percent 图片缩放比例
     */
    public static function Compress($src, $percent = 0)
    {
        (new self($src, $percent))->debugInfo('first function Compress', 'start...', '+-');
        // return true;
        (new self($src, $percent))->compressImg($src);
    }

    /**
     * 高清压缩图片
     *
     * @param string $saveName
     */
    public function compressImg($saveName = '')
    {
        if ($this->image_lib == 'gd') {
            if ($this->compress_quality > 9) {
                $this->compress_quality = ceil($this->compress_quality / 120 * 10) - 1;
                $this->debugInfo('compressImg', ['compress_quality' => $this->compress_quality, 'gd_info' => gd_info()]);
            }
            $this->_GD_openImage();
            if (!empty($saveName)) $this->_GD_saveImage($saveName);  //保存
            else $this->_GD_showImage();
        } else {
            $this->_IMAGICK_openImage();
            if (!empty($saveName)) $this->_IMAGICK_saveImage($saveName);  //保存
            else $this->_IMAGICK_showImage();
        }
    }

    /**
     * 设置压缩质量，
     * 整数区间1-100
     *
     * @param int $quality 压缩质量整数
     * @return $this
     */
    public function setQuality($quality = 50)
    {
        $quality = intval($quality);
        $quality = ($quality >= 0 && $quality <= 100) ? $quality : 50;
        $this->compress_quality = $quality;
        return $this;
    }

    /**
     * 设置最大宽度，
     * 超过最大宽度，将按比例缩放
     *
     * @param int $width 允许的最大宽度
     * @return $this
     */
    public function setMaxWidth($width = 1100)
    {
        $this->thumb_threshold = $width;
        return $this;
    }

    /**
     * 设置拷贝模式，
     * 图片处理完，拷贝覆盖原图
     *
     * @param bool $copy_mode
     * @return $this
     */
    public function setCopyMode($copy_mode = true)
    {
        $this->copy_mode = $copy_mode;
        return $this;
    }
    /**
     * 调试信息，
     * 日志目录：/Temp/Admin/Bone/
     *
     * @param string $title 标题
     * @param string $msg 详细内容
     * @param string $separator 分隔符,默认为等号'='
     * @return void
     */
    public function debugInfo($title, $msg = '', $separator = '')
    {
        if ($this->debug) {
            $title .= ':[' . date('Y/m/d H:i:s') . ']';
            //日志内容格式
            $separator = $separator ? str_repeat($separator, 30) : str_repeat('=', 30);
            $content = $title . ":\n" . (is_array($msg) ? var_export($msg, true) : $msg) . "\n" . $separator . "<br>\n";
            file_put_contents($this->debug_file, $content, FILE_APPEND);
        }
    }
    //
    //{{
    //GD库方法

    /**
     * 内部：打开图片
     * @throws
     */
    private function _GD_openImage()
    {
        list($width, $height, $type, $attr) = getimagesize($this->src);
        $this->imageinfo = array(
            'width'=>$width,
            'height'=>$height,
            'type'=>image_type_to_extension($type,false),
            'attr'=>$attr
        );

        if ($this->auto_thumb && $this->imageinfo['width'] > $this->thumb_threshold && !$this->percent) {
            $this->percent = round($this->thumb_threshold / $this->imageinfo['width'], 1);
        }
        $fun = "imagecreatefrom".$this->imageinfo['type'];
        $this->image = $fun($this->src);
        $this->debugInfo('GD_openImage', [$fun => $this->image ? 'success' : 'error']);

        $this->_GD_thumpImage();
    }

    /**
     * 内部：操作图片
     *
     * change log:
     * 2019-4-11: 为PNG，GIF格式图片增加透明属性，避免黑背景
     */
    private function _GD_thumpImage()
    {
        $new_width = $this->percent ? $this->imageinfo['width'] * $this->percent : $this->imageinfo['width'];
        $new_height = $this->percent ? $this->imageinfo['height'] * $this->percent : $this->imageinfo['height'];
        $this->debugInfo('GD_width_height', [
            'percent' => $this->percent,
            'old_width' => $this->imageinfo['width'],
            'old_height' => $this->imageinfo['height'],
            'new_widtht' => $new_width,
            'new_height' => $new_height,
        ]);
        $image_thump = imagecreatetruecolor($new_width,$new_height);
        $this->debugInfo('GD_thumpImage', ['imagecreatetruecolor' => $image_thump ? 'success' : 'error']);

        //如果是PNG，GIF格式，启用alpha通道属性
        if (in_array($this->imageinfo['type'], ['png', 'gif'])) {
            imagealphablending($image_thump, true);
            $transparent = imagecolorallocatealpha($image_thump, 0, 0, 0, 127);
            imagefill($image_thump, 0, 0, $transparent);

            $r = imagecopyresampled($image_thump, $this->image, 0, 0, 0, 0, $new_width, $new_height, $this->imageinfo['width'], $this->imageinfo['height']);
            $this->debugInfo('GD_thumpImage', ['imagecopyresampled_alpha' => $r ? 'success' : 'error']);
            imagealphablending($image_thump, false);
            imagesavealpha($image_thump, true);
        } //非PNG，GIF格式图片直接合并
        else {
            $r = imagecopyresampled($image_thump, $this->image, 0, 0, 0, 0, $new_width, $new_height, $this->imageinfo['width'], $this->imageinfo['height']);
            $this->debugInfo('GD_thumpImage', ['imagecopyresampled' => $r ? 'success' : 'error']);
        }

        imagedestroy($this->image);
        $this->image = $image_thump;
    }

    /**
     * 输出图片:保存图片则用saveImage()
     */
    private function _GD_showImage()
    {
        header('Content-Type: image/'.$this->imageinfo['type']);
        $funcs = "image".$this->imageinfo['type'];
        $r = $funcs($this->image);
        $this->debugInfo('GD_showImage', ['image_type_func' => $r ? 'success' : 'error']);
    }
    /**
     * 保存图片到硬盘：
     * @param  string $dstImgName  1、可指定字符串不带后缀的名称，使用源图扩展名 。2、直接指定目标图片名带扩展名。
     * @return bool
     */
    private function _GD_saveImage($dstImgName)
    {
        if(empty($dstImgName)) return false;
        $allowImgs = ['.jpg', '.jpeg', '.png', '.bmp', '.wbmp','.gif'];   //如果目标图片名有后缀就用目标图片扩展名 后缀，如果没有，则用源图的扩展名
        $dstExt =  strrchr($dstImgName ,".");
        $sourseExt = strrchr($this->src ,".");
        if(!empty($dstExt)) $dstExt =strtolower($dstExt);
        if(!empty($sourseExt)) $sourseExt =strtolower($sourseExt);

        //有指定目标名扩展名
        if(!empty($dstExt) && in_array($dstExt,$allowImgs)){
            $dstName = $dstImgName;
        }elseif(!empty($sourseExt) && in_array($sourseExt,$allowImgs)){
            $dstName = $dstImgName.$sourseExt;
        }else{
            $dstName = $dstImgName.$this->imageinfo['type'];
        }
        if ($this->copy_mode) {
            $dstName = str_replace("{$dstExt}", "_s{$dstExt}", $dstName);
        }
        // $filesize = filesize($dstImgName)/1024 . ' KB';
        $funcs = "image".$this->imageinfo['type'];
        if (in_array($this->imageinfo['type'], ['png', 'jpg', 'jpeg'])) {
            //jpg type quality is 0-100, png is 0-9
            if ($this->imageinfo['type'] !== 'png') {
                $this->compress_quality = ($this->compress_quality + 1) * 10;
            }
            $funcs($this->image, $dstName, $this->compress_quality);
        } else {
            $funcs($this->image, $dstName);
        }

        $debugInfo = [
            'compress_quality' => $this->compress_quality,
            'source_image' => $dstImgName,
            'source_image_exist' => file_exists($dstImgName) ? 'yes' : 'no',
            'new_image' => $dstName,
            'new_image_size' => filesize($dstName) / 1024 . ' KB',
            'new_image_exist' => file_exists($dstName) ? 'yes' : 'no',
        ];
        if ($this->copy_mode) {
            $c = (is_readable($dstName) && is_writable($dstImgName)) ? copy($dstName, $dstImgName) : false;
            $debugInfo['copy_status'] = $c;
        }
        $this->debugInfo('GD_saveImage', $debugInfo);

        return true;
    }

    /**
     * 销毁图片
     */
    public function __GD_destruct()
    {
        imagedestroy($this->image);
    }

    //end GD
    //}}
    //

    //
    //ImageMagick方法
    //{{
    /**
     * 内部：打开图片
     * @throws
     */
    private function _IMAGICK_openImage()
    {
        $this->debugInfo('getimagesize', 'before');
        list($width, $height, $type, $attr) = getimagesize($this->src);
        $this->debugInfo('getimagesize', 'after');
        $this->debugInfo('image_type_to_extension', 'before');
        $this->imageinfo = array(
            'width' => $width,
            'height' => $height,
            'type' => image_type_to_extension($type, false),
            'attr' => $attr
        );
        $this->debugInfo('image_type_to_extension', 'after');
        if ($this->auto_thumb && $this->imageinfo['width'] > $this->thumb_threshold && !$this->percent) {
            $this->percent = round($this->thumb_threshold / $this->imageinfo['width'], 1);
        }

        $image = new \Imagick($this->src);
        $this->debugInfo('IMAGICK_openImage', ['Imagick_init' => 'started']);
        $this->image = clone $image;
        $this->debugInfo('IMAGICK_openImage', 'clone image object');
        $this->_IMAGICK_thumpImage();

    }

    /**
     * 内部：操作图片
     */
    private function _IMAGICK_thumpImage()
    {
        $new_width = $this->percent ? $this->imageinfo['width'] * $this->percent : $this->imageinfo['width'];
        $new_height = $this->percent ? $this->imageinfo['height'] * $this->percent : $this->imageinfo['height'];
        $this->debugInfo('IMAGICK_thumpImage', 'cal width');
        if (in_array($this->imageinfo['type'], ['jpg', 'jpeg'])) {
            $this->image->setImageCompression(\Imagick::COMPRESSION_JPEG);
        }
        $this->image->setImageCompressionQuality($this->compress_quality);
        $this->debugInfo('IMAGICK_thumpImage', 'setImageCompressionQuality');

        $this->image->thumbnailImage($new_width, $new_height);
        $this->debugInfo('IMAGICK_thumpImage', 'thumbnailImage');
        $this->debugInfo('IMAGICK_thumpImage', [
            'new_width' => $new_width,
            'old_width' => $this->imageinfo['width'],
            'compress_quality' => $this->compress_quality,
        ]);
    }

    /**
     * 输出图片:保存图片则用saveImage()
     */
    private function _IMAGICK_showImage()
    {
        header('Content-Type: image/' . $this->imageinfo['type']);
        $this->image->getImageBlob();
        $this->debugInfo('IMAGICK_thumpImage', 'show getImageBlob() content');
    }
    /**
     * 保存图片到硬盘：
     * @param  string $dstImgName 1、可指定字符串不带后缀的名称，使用源图扩展名 。2、直接指定目标图片名带扩展名。
     * @return bool|mixed
     * throws \ImagickException
     */
    private function _IMAGICK_saveImage($dstImgName)
    {

        if (empty($dstImgName)) return false;
        $allowImgs = ['.jpg', '.jpeg', '.png', '.bmp', '.wbmp', '.gif'];   //如果目标图片名有后缀就用目标图片扩展名 后缀，如果没有，则用源图的扩展名
        $dstExt = strrchr($dstImgName, ".");
        $sourseExt = strrchr($this->src, ".");
        if (!empty($dstExt)) $dstExt = strtolower($dstExt);
        if (!empty($sourseExt)) $sourseExt = strtolower($sourseExt);

        //有指定目标名扩展名
        if (!empty($dstExt) && in_array($dstExt, $allowImgs)) {
            $dstName = $dstImgName;
        } elseif (!empty($sourseExt) && in_array($sourseExt, $allowImgs)) {
            $dstName = $dstImgName . $sourseExt;
        } else {
            $dstName = $dstImgName . $this->imageinfo['type'];
        }
        $this->debugInfo('IMAGICK_saveImage', $dstName);

        if ($this->copy_mode) {
            $dstName = str_replace("{$dstExt}", "_s{$dstExt}", $dstName);
            $this->image->writeImage($dstName);
            $c = (is_readable($dstName) && is_writable($dstImgName)) ? copy($dstName, $dstImgName) : false;
        } else {
            $this->image->writeImage($dstName);
        }

        $debugInfo = [
            'compress_quality' => $this->compress_quality,
            'source_image' => $this->src,
            'source_image_exist' => file_exists($this->src) ? 'yes' : 'no',
            'new_image' => $dstName,
            'new_image_exist' => file_exists($dstName) ? 'yes' : 'no',
            'new_image_size' => filesize($dstName) / 1024 . ' KB',
        ];
        if ($this->copy_mode) {
            $debugInfo['copy_status'] = $c;
        }

        $this->debugInfo('IMAGICK_saveImage', $debugInfo);
        return true;
    }

    /**
     * 销毁图片
     */
    public function __IMAGICK_destruct()
    {
        $this->image->clear();
    }
    //end ImageMagick
    //}}
    //

}
