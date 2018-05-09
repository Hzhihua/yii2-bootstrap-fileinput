<?php
/**
 * @Author: cnzhihua
 * @Date: 2018-04-23 18:39
 * @Github: https://github.com/Hzhihua
 */

namespace hzhihua\actions;

use Yii;
use yii\base\Action;
use yii\base\Exception;
use yii\helpers\FileHelper;
use yii\helpers\Url;
use yii\web\UploadedFile;
use hzhihua\actions\traits\I18NTrait;
use hzhihua\actions\traits\EventTrait;
use hzhihua\actions\traits\DirectoryTrait;
use hzhihua\actions\traits\ResponseFormatTrait;

/**
 * Class ImageUploadAction
 * @package hzhihua\actions
 *
 * Add file action to controller
 * ```php
 * public function actions()
 * {
 *     return [
 *         'file-upload' => [
 *              'class' => 'hzhihua\actions\FileUploadAction',
 *              'on beforeUpload' => [new MyFileModel(), 'beforeUpload'],
 *              'on afterUpload' => [new MyFileModel(), 'afterUpload'],
 *              'responseFormat' => 'json', // default json
 *              'deleteAction' => 'file-delete',
 *              'downloadAction' => 'file-download',
 *         ],
 *     ];
 * }
 * ```
 * @see FileTrait
 * @Author: cnzhihua
 * @Github: https://github.com/Hzhihua/yii2-bootstrap-fileinput
 */
class FileUploadAction extends Action
{
    use I18NTrait;
    use EventTrait;
    use DirectoryTrait;
    use ResponseFormatTrait;

    /**
     * before upload event
     */
    const EVENT_BEFORE_UPLOAD = 'beforeUpload';

    /**
     * after upload event
     */
    const EVENT_AFTER_UPLOAD = 'afterUpload';

    /**
     * upload attribute name
     * @var string
     */
    public $attribute = 'picture';

    /**
     * allow file type
     * @var array
     */
    public $fileType = ['jpeg', 'jpg', 'gif', 'png', 'mp4', 'mp3', 'doc', 'docx', 'zip'];

    /**
     * deny file type
     * @var array
     */
    public $denyFileType = [];

    /**
     * allow max file size
     * Default: no limit
     * @var int
     */
    public $maxFileSize = 0;

    /**
     * allow min file size
     * Default: no limit
     * @var int
     */
    public $minFileSize = 0;

    /**
     * customize your see url, you can change it by EVENT_BEFORE_UPLOAD
     *
     * ```php
     * public function beforeUpload(\hzhihua\actions\Event $event)
     * {
     *     $newDirectory = $event->sender->newDirectory();
     *     // or $newDirectory = 'a/b/c';
     *
     *     $newFileName = $event->sender->newName();
     *     // or $newFileName = 'my_new_file';
     *
     *     $extension = $event->file->extension;
     *
     *     $event->sender->seeUrl = "www.mydomain.com/{$newDirectory}/{$newFileName}.{$extension}";
     * }
     * ```
     *
     * Default:
     * @see getSeeUrl
     * @var string
     */
    public $seeUrl;

    /**
     * Default: @webroot/img/temp/
     * **only $seeUrl is empty**
     * @var string
     */
    public $seeDirectory;

    /**
     * customize your deteleUrl, it must include key_file param
     * and you can change it by EVENT_BEFORE_UPLOAD
     *
     * ```php
     * public function beforeUpload(\hzhihua\actions\Event $event)
     * {
     *      // file key param must be exists
     *      $key = $event->sender->getFileKey();
     *      $event->sender->deleteUrl = "www.mydomain.com/delete?file_key={$key}";
     * }
     * ```
     *
     * Default:
     * @see getDeleteUrl
     * @var string
     */
    public $deleteUrl;

    /**
     * action for file delete
     * Url::to(['file-delete', 'file_key' => $fileKey])
     * **only $deleteUrl is empty**
     * @var string
     */
    public $deleteAction = 'file-delete';

    /**
     * customize your downloadUrl, it must include key_file param
     * and you can change it by EVENT_BEFORE_UPLOAD
     *
     * ```php
     * public function beforeUpload(\hzhihua\actions\Event $event)
     * {
     *      // file key param must be exists
     *      $key = $event->sender->getFileKey();
     *      $event->sender->downloadUrl = "www.mydomain.com/download?file_key={$key}";
     * }
     * ```
     * @var string
     */
    public $downloadUrl;

    /**
     * action for file download
     * ```php
     * Url::to(['file-download', 'file_key' => $fileKey])
     * ```
     **only $downloadUrl is empty**
     * @var string
     */
    public $downloadAction = 'file-download';

    /**
     * 一维数组
     * @var array
     */
    public $initialPreview = [];

    /**
     * 二维数组
     * @var array
     */
    public $initialPreviewConfig = [];

    /**
     * new file name
     * you can customize it by EVENT_BEFORE_UPLOAD
     *
     * ```php
     * public function beforeUpload(\hzhihua\actions\Event $event)
     * {
     *      $event->sender->newName = 'my_new_file_name';
     * }
     * ```
     *
     * Default:
     * @see getNewName
     * @var string
     */
    public $newName;

    /**
     * new directory, it may be different for per request
     * you can customize it by EVENT_BEFORE_UPLOAD
     *
     * For example, make the new directory by date
     * ```
     * 05/05/2018
     * ```
     *
     * ```php
     * public function beforeUpload(\hzhihua\actions\Event $event)
     * {
     *      $event->sender->newDirectory = '/05/05/2018/';
     * }
     * ```
     *
     * Default:
     * @see getNewDirectory
     * @var string
     */
    public $newDirectory;

    /**
     * the file unique key, also you can customize it by EVENT_BEFORE_UPLOAD
     *
     * ```php
     * public function beforeUpload(\hzhihua\actions\Event $event)
     * {
     *      $event->sender->fileKey = unique();
     * }
     * ```
     * @var string
     */
    public $fileKey;

    /**
     * 是否把这些配置加入`initialPreview`。
     * 如果设置为`false`，它会重载初始预览。
     * 如果设置为`true`，它会加入初始预览之中。
     * 如果这个属性没有被设置或者没有传出，它会默认为`true`。
     * @see https://github.com/kartik-v/bootstrap-fileinput/wiki/12.-%E4%B8%80%E4%BA%9B%E6%A0%B7%E4%BE%8B%E4%BB%A3%E7%A0%81
     * @var bool
     */
    public $append = true;

    /**
     * errors info
     * @var array
     */
    public $errors = [
        1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
        2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
        3 => 'The uploaded file was only partially uploaded.',
        4 => 'No file was uploaded.',
        6 => 'Missing a temporary folder.',
        7 => 'Failed to write file to disk.',
        8 => 'A PHP extension stopped the file upload.',
    ];

    /**
     * init variable
     */
    public function init()
    {
        parent::init();

        $this->seeDirectory || $this->seeDirectory = Yii::$app->params['baseUrl'];

    }

    /**
     * @return bool
     */
    public function beforeRun()
    {
        $this->setI18N();
        $this->setResponseFormat();
        $this->handleUploadDirectory();
        return parent::beforeRun();
    }

    /**
     * 入口
     */
    public function run()
    {

        try {

            $file = $this->getFile($this->attribute);

            $event = $this->getEvent();
            $event->file = $file;
            $event->fileKey = $this->getFileKey();

            $this->trigger(self::EVENT_BEFORE_UPLOAD, $event);
            $this->validateFile($file);
            $this->saveImage($file);
            $this->trigger(self::EVENT_AFTER_UPLOAD, $event);

            /**
             * 处理ajax上传
             * 服务器方法必须返回一个包含`initialPreview`、`initialPreviewConfig`和`append`的JSON对象。
             * @see https://github.com/kartik-v/bootstrap-fileinput/wiki/12.-%E4%B8%80%E4%BA%9B%E6%A0%B7%E4%BE%8B%E4%BB%A3%E7%A0%81
             */
            return [
                'file_key' => $this->getFileKey(),
                'initialPreview' => $this->getInitialPreview($file),
                'initialPreviewConfig' => $this->getInitialPreviewConfig($file),

                'append' => $this->append,
                // 是否把这些配置加入`initialPreview`。
                // 如果设置为`false`，它会重载初始预览。
                // 如果设置为`true`，它会加入初始预览之中。
                // 如果这个属性没有被设置或者没有传出，它会默认为`true`。
            ];
        } catch (ValidateException $e) {
            Yii::warning($e->getMessage(), __METHOD__);
            return ['error' => $e->getMessage()];

        } catch (Exception $e) {
            Yii::warning($e->getMessage(), __METHOD__);
            return ['error' => $e->getMessage()];
//            return ['error' => Yii::t('upload', 'Upload file failed, please try again.')];
        }
    }

    /**
     * @param UploadedFile $file
     * @return string
     */
    public function getNewName(UploadedFile $file)
    {
        if (empty($this->newName)) {
            $this->newName = md5($file->baseName.time());
            Yii::info("Generate file name from {$file->name} to {$this->newName}.{$file->extension}", __METHOD__);
        }
        return $this->newName;
    }

    /**
     * new directory, it may be different from per request
     * For example:
     * ```
     * 05/05/2018
     * ```
     * @return string
     */
    public function getNewDirectory()
    {
        if (empty($this->newDirectory)) {
            $time = time();
            $this->newDirectory = sprintf(
                '%s/%s/%s',
                date('y', $time),
                date('m', $time),
                date('d', $time)
            );
        } else {
            $this->newDirectory = trim($this->newDirectory, '/');
        }

        return $this->newDirectory;
    }

    /**
     * @param UploadedFile $file
     * @return array
     */
    public function getInitialPreview(UploadedFile $file)
    {
        if (empty($this->initialPreview)) {
            $this->initialPreview = [
                $this->getSeeUrl($file),
            ];
        }

        return $this->initialPreview;
    }

    /**
     * @param UploadedFile $file
     * @return array
     */
    public function getInitialPreviewConfig(UploadedFile $file)
    {
        if (empty($this->initialPreviewConfig)) {
            $this->initialPreviewConfig = [[
                'caption' => $file->name,
                'size' => $file->size,
                'downloadUrl' => $this->getDownloadUrl(), // download url
                'url' => $this->getDeleteUrl(), // delete url
            ]];
        }

        return $this->initialPreviewConfig;
    }

    /**
     * @param UploadedFile $file
     * @return mixed|string
     */
    public function getSeeUrl(UploadedFile $file)
    {
        if (empty($this->seeUrl)) {
            $newName = $this->getNewName($file);
            $baseUrl = $this->seeDirectory . $this->handleDirectoryFormat($this->getNewDirectory());
            $this->seeUrl = $baseUrl . $newName . '.' . $file->extension;
        }

        return $this->seeUrl;
    }

    /**
     * @return string
     */
    public function getDeleteUrl()
    {
        if (empty($this->deleteUrl)) {
            $key = $this->getFileKey();
            $this->deleteUrl = Url::to([$this->deleteAction, 'file_key' => $key]);
        }

        return $this->deleteUrl;
    }

    /**
     * @return string
     */
    public function getDownloadUrl()
    {
        if (empty($this->downloadUrl)) {
            $key = $this->getFileKey();
            $this->downloadUrl = Url::to([$this->downloadAction, 'file_key' => $key]);
        }

        return $this->downloadUrl;
    }

    /**
     * 获取文件标识,fileKey参数是必须要的
     * @return string
     */
    public function getFileKey()
    {
        if (empty($this->fileKey)) {
            $this->fileKey = uniqid();
        }

        return $this->fileKey;
    }

    /**
     * @param $attribute
     * @return null|UploadedFile
     * @throws Exception
     */
    public function getFile($attribute)
    {
        $file = UploadedFile::getInstanceByName($attribute);

        if (null === $file) {
            throw new Exception("Could not instantiation file object by attribute name \"{$attribute}\"");
        } elseif ($file->hasError) {
            throw new Exception($this->errors[$file->error]);
        }

        return $file;
    }

    /**
     * @param UploadedFile $file
     * @return bool
     * @throws Exception
     */
    public function saveImage(UploadedFile $file)
    {
        $path = $this->uploadDirectory . $this->handleDirectoryFormat($this->getNewDirectory());
        is_dir($path) || FileHelper::createDirectory($path);

        $newName = "{$this->getNewName($file)}.{$file->extension}";
        return $file->saveAs($path . $newName);
    }

    /**
     * @param UploadedFile $file
     * @throws ValidateException
     */
    public function validateFile(UploadedFile $file)
    {
        if (in_array($file->extension, $this->denyFileType) || !in_array($file->extension, $this->fileType)) {
            throw new ValidateException(Yii::t('upload', 'File type is not allow'));
        }

        if (
            (!empty($this->minFileSize) && $file->size < (int) $this->minFileSize) ||
            (!empty($this->maxFileSize) && $file->size > (int) $this->maxFileSize)
        ) {
            throw new ValidateException(Yii::t('upload', 'File size is not allow'));
        }
    }

}