<?php
/**
 * @Author: cnzhihua
 * @Date: 2018-05-04 23:57
 * @Github: https://github.com/Hzhihua
 */

namespace hzhihua\actions;

class UploadedFile extends \yii\web\UploadedFile
{
    /**
     * File extension.
     * @var string
     */
    public $extension;

    /**
     * Original file base name.
     * @var string
     */
    public $baseName;

    /**
     * 文件访问目录
     * 如： 2018/05/05/abc.png
     * @var string
     */
    public $url;

    /**
     * 新的文件名
     * @var string
     */
    public $newName;

}