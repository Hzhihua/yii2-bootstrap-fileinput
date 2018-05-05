<?php
/**
 * @Author: cnzhihua
 * @Date: 2018-05-02 20:54
 * @Github: https://github.com/Hzhihua
 */

namespace hzhihua\actions;

use yii\base\ActionEvent;

class Event extends ActionEvent
{
    /**
     * @var object the sender of this event. If not set, this property will be
     * set as the object whose `trigger()` method is called.
     * This property may also be a `null` when this event is a
     * class-level event which is triggered in a static context.
     */
    public $sender;

    /**
     * @var UploadedFile
     */
    public $file;

    /**
     * file unique key, generate by unique()
     * @var string
     */
    public $fileKey;

}