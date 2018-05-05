<?php
/**
 * @Author: cnzhihua
 * @Date: 2018-04-23 19:54
 * @Github: https://github.com/Hzhihua
 */

namespace hzhihua\actions;

use hzhihua\actions\traits\I18NTrait;
use Yii;
use yii\base\Action;
use yii\base\Exception;
use yii\helpers\FileHelper;
use yii\web\NotFoundHttpException;
use hzhihua\actions\traits\EventTrait;
use hzhihua\actions\traits\DirectoryTrait;
use hzhihua\actions\traits\ResponseFormatTrait;

/**
 * Class FileDeleteAction
 * @package hzhihua\actions
 *
 * Add file action to controller
 * ```php
 * public function actions()
 * {
 *     return [
 *         'file-delete' => [
 *              'class' => 'hzhihua\actions\FileDeleteAction',
 *              'on beforeDelete' => [new MyFileModel(), 'beforeDelete'], // must be
 *              'on afterDelete' => [new MyFileModel(), 'afterDelete'], // optional
 *              //'responseFormat' => 'json', // error info format, default json
 *         ],
 *     ];
 * }
 * ```
 *
 * And then, get the file info from database, it is great importance!
 * ```php
 * public function beforeDelete(\hzhihua\actions\Event $event)
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
class FileDeleteAction extends Action
{
    use I18NTrait;
    use EventTrait;
    use DirectoryTrait;
    use ResponseFormatTrait;

    /**
     * event before delete
     */
    const EVENT_BEFORE_DELETE = 'beforeDelete';

    /**
     * event after delete
     */
    const EVENT_AFTER_DELETE = 'afterDelete';

    /**
     * get the new file name from database
     * **you must set it in EVENT_BEFORE_DELETE**
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
     * **you must set it in EVENT_BEFORE_DELETE**
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
     * @return array
     * @throws \yii\base\InvalidConfigException
     */
    public function run()
    {

        try {

            $fileKey = $this->getFileKey();
            $event = $this->getEvent();
            $event->fileKey = $fileKey;

            $this->trigger(self::EVENT_BEFORE_DELETE, $event);

            // include file extension
            $file = $this->getFilePath();
            $filename = $this->getFileName();

            if (!(
                is_file($file) &&
                preg_match('/^[a-z0-9_]+\.[a-z]+$/i', $filename) &&
                FileHelper::unlink($file)
            )) {
                throw new NotFoundHttpException(Yii::t('upload', "Delete file \"{filename}\" failed, file not found.", ['filename' => $event->file->name]));
            }

            $this->trigger(self::EVENT_AFTER_DELETE, $event);

            Yii::info("Delete file[$file] by file key \"{$fileKey}\" success", __METHOD__);
            return ['msg' => "ok"];

        } catch (NotFoundHttpException $e) {
            $error = $e->getMessage();
            $key = $this->getFileKey();
            $file = $this->getFilePath();

            Yii::warning("Delete file[$file] failed by the file key \"{$key}\", for \"{$error}\"", __METHOD__);
            return ['error' => $error];

        } catch (Exception $e) {
            $error = $e->getMessage();
            $key = $this->getFileKey();
            $file = $this->getFilePath();

            Yii::warning("Delete file[$file] failed by the file key \"{$key}\", for \"{$error}\"", __METHOD__);
            return ['error' => Yii::t('upload', 'Delete file failed, please try again.')];
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