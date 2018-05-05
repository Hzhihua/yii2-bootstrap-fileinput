<?php
/**
 * @Author: cnzhihua
 * @Date: 2018-05-04 11:11
 * @Github: https://github.com/Hzhihua
 */

namespace hzhihua\actions\traits;

use Yii;
use yii\web\Response;

trait ResponseFormatTrait
{
    /**
     * response format
     *
     * Default: json
     *
     * @var string
     */
    public $responseFormat = null;

    /**
     * set response format
     */
    public function setResponseFormat()
    {
        $this->responseFormat || $this->responseFormat = Response::FORMAT_JSON;
        Yii::$app->response->format = $this->responseFormat;
        Yii::info("Set response format to {$this->responseFormat}", __METHOD__);
    }
}