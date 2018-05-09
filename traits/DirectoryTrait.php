<?php
/**
 * @Author: cnzhihua
 * @Date: 2018-05-04 11:23
 * @Github: https://github.com/Hzhihua
 */

namespace hzhihua\actions\traits;

use Yii;

trait DirectoryTrait
{
    /**
     * @var string
     */
    public $uploadDirectory = '@source/';

    /**
     * handle upload directory
     */
    public function handleUploadDirectory()
    {
        $this->uploadDirectory = $this->handleDirectoryFormat($this->uploadDirectory, '/');
    }

    /**
     * result:
     * ```
     * abc/edf/
     * ```
     * @param string $path
     * @param string $prefix
     * @return string
     */
    public function handleDirectoryFormat($path, $prefix = '')
    {
        return $prefix . trim(Yii::getAlias($path), '/') . '/';
    }
}