<?php
/**
 * @Author: cnzhihua
 * @Date: 2018-05-05 10:32
 * @Github: https://github.com/Hzhihua
 */

namespace hzhihua\actions;

use Yii;

/**
 * This file is a template of FileTrait
 *
 * You trait:
 * ```php
 * trait MyFileTrait
 * {
 *
 * }
 *
 * class File extends \yii\db\ActiveRecord
 * {
 *      use MyFileTrait;
 * }
 * ```
 */
trait FileTrait
{
    /**
     * @param Event $event
     */
    public function beforeImageUpload(Event $event)
    {
//        $event->sender->newName = 'new_file_name';
//        $event->sender->newDirectory = '/a/b/c/';
    }

    /**
     * @param Event $event
     * @throws \Throwable
     */
    public function afterImageUpload(Event $event)
    {
        /* @var {$event->sender} \hzhihua\actions\ImageUploadAction */
        $this->file_key = $event->fileKey;
        $this->type = $event->file->type;
        $this->size = $event->file->size;
        $this->extension = $event->file->extension;
        $this->origin_name = $event->file->baseName;

        $this->new_name = $event->sender->newName;
        $this->new_directory = $event->sender->newDirectory;

        $event->isValid = $this->validate() && $this->insert();
    }

    /**
     * @param Event $event
     */
    public function beforeImageDelete(Event $event)
    {
        $data = static::find()->where(['file_key' => $event->fileKey])->asArray()->one();

        if ($data) {
            $file = new UploadedFile();
            $file->type = $data['type'];
            $file->size = (int) $data['size'];
            $file->extension = $data['extension'];
            $file->baseName = $data['origin_name'];
            $file->name = $data['origin_name'] . '.' . $data['extension'];

            $event->sender->newName = $data['new_name'];
            $event->sender->newDirectory = $data['new_directory'];
            $event->file = $file;
        } else {
            $event->isValid = false;
        }
    }

    /**
     * @param Event $event
     * @throws \yii\db\Exception
     */
    public function afterImageDelete(Event $event)
    {
        $rst = Yii::$app->db->createCommand()
            ->delete(static::tableName(), ['file_key' => $event->fileKey])
            ->execute();
        $event->isValid = boolval($rst);
    }

    /**
     * @param Event $event
     */
    public function beforeDownload(Event $event)
    {
        $this->beforeImageDelete($event);
    }

    /**
     * @param Event $event
     */
    public function afterDownload(Event $event)
    {
        // do something
    }
}