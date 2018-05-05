<?php
/**
 * @Author: cnzhihua
 * @Date: 2018-05-05 13:31
 * @Github: https://github.com/Hzhihua
 */

namespace hzhihua\actions;

use Throwable;
use yii\base\Exception;

class ValidateException extends Exception
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}