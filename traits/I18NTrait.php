<?php
/**
 * @Author: cnzhihua
 * @Date: 2018-05-04 11:32
 * @Github: https://github.com/Hzhihua
 */

namespace hzhihua\actions\traits;

use Yii;
use yii\helpers\ArrayHelper;

trait I18NTrait
{
    /**
     * @var array
     */
    public $translation = [
        'upload*' => [
            'class' => 'yii\i18n\PhpMessageSource',
            'basePath' => '@backend/actions/messages',
            'fileMap' => [
                'upload' => 'upload.php',
            ],
        ],
    ];

    public function setI18N()
    {
        Yii::$app->i18n->translations = ArrayHelper::merge(Yii::$app->i18n->translations, $this->translation);
    }
}