<?php
/**
 * @Author: cnzhihua
 * @Date: 2018-05-05 10:29
 * @Github: https://github.com/Hzhihua
 */

namespace hzhihua\actions;

use yii\helpers\Url;
use yii\db\ActiveRecord;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;

/**
 * Class FormHelper
 * @package hzhihua\actions
 *
 * 1. open view file
 * 2. add <?= \hzhihua\actions\FormHelper::ImageUpload($form, $model, 'picture_id') ?>
 */
class FormHelper
{
    /**
     * 处理ajax上传并返回JSON响应的服务器代码。
     * 服务器方法必须返回一个包含`initialPreview`、`initialPreviewConfig`和`append`的JSON对象。
     * add to view file
     * ```php
     *<?= \hzhihua\actions\FormHelper::ImageUpload($form, $model, 'picture_id') ?>
     * ```
     *
     * @param ActiveForm $form
     * @param ActiveRecord $model
     * @param string $attribute
     * @param array $clientOptions
     * @return string
     * @throws \Exception
     * @see http://demos.krajee.com/widget-details/fileinput#advanced-usage
     * @see https://github.com/kartik-v/bootstrap-fileinput/wiki/10.-%E4%BA%8B%E4%BB%B6
     * @see https://github.com/kartik-v/bootstrap-fileinput/wiki/12.-%E4%B8%80%E4%BA%9B%E6%A0%B7%E4%BE%8B%E4%BB%A3%E7%A0%81
     */
    public static function ImageUpload(ActiveForm $form, $model, $attribute, array $clientOptions = [])
    {
        $inputId = static::getInputId();
        $alertMsg = 'Are you sure you want to delete this file?';
        $clientOptions = ArrayHelper::merge([
            'name' => 'picture',
            'options'=>[
                'multiple' => false, // 多文件上传
                'accept' => 'image/*',
            ],
            'pluginEvents' => [
                'filepredelete' => 'function (jqXHR) {var abort = true;if (confirm("'.$alertMsg.'")) {abort = false;}return abort;}',
                'fileuploaded' => 'function (event, data) {var key = data.response.file_key;$("#'.$inputId.'").val(key)}'
            ],
            'pluginOptions' => [
                'browseOnZoneClick' => true, // 点击打开文件选择
                'uploadAsync' => true, // ajax异步上传
                'uploadUrl' => Url::to(['image-upload']), // 上传URL
                'deleteUrl' => Url::to(['image-delete']),
                'previewFileType' => 'image/*',
                'initialPreview' => [],
                'overwriteInitial' => false,
                'initialPreviewAsData' => true,
                'initialPreviewFileType' => 'image',
                'initialCaption' => $model->$attribute,
                'initialPreviewConfig' => [],
                'maxFileSize' => 5120,
            ]
        ], $clientOptions);

        return static::upload($form, $model, $attribute, $clientOptions);
    }

    /**
     * @param ActiveForm $form
     * @param ActiveRecord $model
     * @param string $attribute
     * @param array $clientOptions
     * @return string
     * @throws \Exception
     */
    public static function upload(ActiveForm $form, $model, $attribute, array $clientOptions = [])
    {
        $inputId = static::getInputId();
        $attributeLabels = $model->attributeLabels();
        $html = '<label class="control-label">'. $attributeLabels[$attribute] .'</label>';
        $html .= "<input id='{$inputId}' type='hidden' name='{$attribute}' value='' />";
        $html .= \kartik\file\FileInput::widget($clientOptions);

        return $html;
    }

    /**
     * @return string input id
     */
    public static function getInputId()
    {
        static $id = '';
        if (empty($id)) {
            $id = 'input_' . uniqid();
        }
        return $id;
    }
}