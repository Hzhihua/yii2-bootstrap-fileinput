<?php
/**
 * @Author: cnzhihua
 * @Date: 2018-05-04 11:01
 * @Github: https://github.com/Hzhihua
 */

namespace hzhihua\actions;

use hzhihua\actions\traits\I18NTrait;
use Yii;
use yii\base\Action;
use yii\base\Exception;
use yii\web\NotFoundHttpException;
use hzhihua\actions\traits\EventTrait;
use hzhihua\actions\traits\DirectoryTrait;
use hzhihua\actions\traits\ResponseFormatTrait;

/**
 * Class FileDownloadAction
 * @package hzhihua\actions
 *
 * Add file action to controller
 * ```php
 * public function actions()
 * {
 *     return [
 *         'file-download' => [
 *              'class' => 'hzhihua\actions\FileDownloadAction',
 *              'on beforeDownload' => [new MyFileModel(), 'beforeDownload'], // must be
 *              'on afterDownload' => [new MyFileModel(), 'afterDownload'], // optional
 *              //'responseFormat' => 'json', // error info format, default json
 *         ],
 *     ];
 * }
 * ```
 *
 * And then, get the file info from database, it is great importance!
 * ```php
 * public function beforeDownload(\hzhihua\actions\Event $event)
 * {
 *      $data = static::find()->where(['file_key' => $event->fileKey])->asArray()->one();
 *      if ($data) {
 *          $file = new \hzhihua\actions\UploadedFile();
 *          $file->type = $data['type']; // file MIME type
 *          $file->size = (int) $data['size'];
 *          $file->extension = $data['extension'];
 *          $file->baseName = $data['origin_name']; // origin file name
 *          $file->name = $data['origin_name'] . '.' . $data['extension'];
 *
 *          $event->sender->newName = $data['new_name'];
 *          $event->sender->newDirectory = $data['new_directory'];
 *          $event->file = $file;
 *      } else {
 *          $event->isValid = false;
 *      }
 * }
 * ```
 * @see FileTrait
 * @Author: cnzhihua
 * @Github: https://github.com/Hzhihua/yii2-bootstrap-fileinput
 */
class FileDownloadAction extends Action
{
    use I18NTrait;
    use EventTrait;
    use DirectoryTrait;
    use ResponseFormatTrait;

    /**
     * event before download
     */
    const EVENT_BEFORE_DOWNLOAD = 'beforeDownload';

    /**
     * event after download
     */
    const EVENT_AFTER_DOWNLOAD = 'afterDownload';

    /**
     * get the new file name from database
     * **you must set it in EVENT_BEFORE_DOWNLOAD**
     *
     * ```php
     * public function beforeDelete(\hzhihua\actions\Event $event)
     * {
     *      $key = $event->fileKey;
     *      $data = static::find()->select('new_file_name')->where(['file_key' => $key])->asArray()->one();
     *      $event->sender->newName = $data['new_file_name'];
     * }
     * ```
     *
     * @var string
     */
    public $newName;

    /**
     * get the new directory from database
     * **you must set it in EVENT_BEFORE_DOWNLOAD**
     *
     * ```php
     * public function beforeDelete(\hzhihua\actions\Event $event)
     * {
     *      $key = $event->fileKey;
     *      $data = static::find()->select('new_directory')->where(['file_key' => $key])->asArray()->one();
     *      $event->sender->newDirectory = $data['new_directory'];
     * }
     * ```
     * @var string
     */
    public $newDirectory;

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
     * @return array|\yii\console\Response|\yii\web\Response
     * @throws \yii\base\InvalidConfigException
     */
    public function run()
    {

        try {

            $fileKey = $this->getFileKey();
            $event = $this->getEvent();
            $event->fileKey = $fileKey;

            $this->trigger(self::EVENT_BEFORE_DOWNLOAD, $event);

            // set header
            Yii::$app->response->setDownloadHeaders($event->file->name, $event->file->extension, false, $event->file->size);

            // include file extension
            $file = $this->getFilePath();
            $filename = $this->getFileName();;

            if (!(is_file($file) && preg_match('/^[a-z0-9_]+\.[a-z]+$/i', $filename))) {
                throw new NotFoundHttpException(Yii::t('upload', "The file \"{filename}\" does not exists.", ['filename' => $event->file->name]));
            }

            $this->trigger(self::EVENT_AFTER_DOWNLOAD, $event);

            Yii::info("Download file[{$file}] by file key \"{$fileKey}\"", __METHOD__);
            return Yii::$app->response->sendFile($file, $event->file->name);

        // file not found exception
        } catch (NotFoundHttpException $e) {
            $error = $e->getMessage();
            $key = $this->getFileKey();
            $file = $this->getFilePath();

            Yii::warning("Download file[$file] failed by the file key \"{$key}\", for \"{$error}\"", __METHOD__);
            return ['error' => $error];

        // other exception
        } catch (Exception $e) {
            $error = $e->getMessage();
            $key = $this->getFileKey();
            $file = $this->getFilePath();

            Yii::warning("Download file[$file] failed by the file key \"{$key}\", for \"{$error}\"", __METHOD__);
            return ['error' => Yii::t('upload', 'Download file failed, please try again.')];
        }

    }

    /**
     * @return array|mixed|string
     */
    public function getFileKey()
    {
        static $key = '';

        if (empty($key)) {
            $key = Yii::$app->request->get('file_key');
        }

        return $key;
    }

    /**
     * @return string
     * @throws \yii\base\InvalidConfigException
     */
    public function getFilePath()
    {
        static $file = '';

        if (empty($file)) {
            $event = $this->getEvent();

            // include file extension
            $newFileName = $this->newName  . '.' . $event->file->extension;
            $file = sprintf(
                '%s%s%s',
                $this->uploadDirectory,
                $this->handleDirectoryFormat($this->newDirectory),
                $newFileName
            );
        }

        return $file;
    }

    /**
     * @return string
     * @throws \yii\base\InvalidConfigException
     */
    public function getFileName()
    {
        static $filename = '';

        if (empty($filename)) {
            $event = $this->getEvent();
            $filename = $this->newName . '.' . $event->file->extension;
        }

        return $filename;
    }
}