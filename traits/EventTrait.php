<?php
/**
 * @Author: cnzhihua
 * @Date: 2018-05-04 10:28
 * @Github: https://github.com/Hzhihua
 */

namespace hzhihua\actions\traits;

use Yii;
use hzhihua\actions\Event;

trait EventTrait
{
    /**
     * @var Event
     */
    public $eventClass = 'hzhihua\actions\Event';

    /**
     * @var Event
     */
    private $_event;

    /**
     * @param array $config
     * @return Event|object
     * @throws \yii\base\InvalidConfigException
     */
    public function getEvent($config = [])
    {
        if (empty($this->_event)) {
            /* @see Yii::createObject 位置参数,有序 */
            $params[] = Yii::$app->controller->action->id;
            $params[] = $config;
            $this->_event = Yii::createObject($this->eventClass, $params);
        }

        return $this->_event;
    }
}