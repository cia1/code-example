<?php

use frontend\modules\integration\models\VkSettingsForm;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;
use yii\widgets\ActiveForm;

/**
 * @var View           $this
 * @var VkSettingsForm $model
 */
$this->title = 'Интеграция ВКонтакте';
?>

<div class="row small-boxes">
    <?php $form = ActiveForm::begin([
        'enableAjaxValidation' => true,
        'enableClientValidation' => false,
        'action' => ['/integration/vk/setting', 'backUrl' => '/integration/vk'],
        'options' => [
            'enctype' => 'multipart/form-data',
            'id' => 'form-update-company',
        ],
    ]); ?>

    <?= $form->errorSummary($model) ?>

    <div class="col-md-6">
        <div class="portlet box darkblue">
            <div class="portlet-title">
                <div class="caption">Подключение к VK Ads</div>
            </div>
            <div class="portlet-body">
                <?= $form->field($model, 'accountId') ?>
            </div>
        </div>
    </div>

</div>

<p>&laquo;<strong>Номер кабинета</strong>&raquo; можно найти на странице <a href="https://vk.com/ads?act=settings" target="_blank">Настройки</a>.</p>

<div class="form-actions">
    <div class="row action-buttons">
        <div class="spinner-button col-sm-1 col-xs-1">
            <?= Html::submitButton('<span class="ladda-label">Сохранить</span><span class="ladda-spinner"></span>', [
                'class' => 'btn darkblue text-white widthe-100 hidden-md hidden-sm hidden-xs mt-ladda-btn _ladda-button',
                'data-style' => 'expand-right',
            ]); ?>
            <?= Html::submitButton('<i class="fa fa-floppy-o fa-2x"></i>', [
                'class' => 'btn darkblue text-white widthe-100 hidden-lg',
                'title' => 'Сохранить',
            ]); ?>
        </div>
        <div class="button-bottom-page-lg col-sm-1 col-xs-1"></div>
        <div class="button-bottom-page-lg col-sm-1 col-xs-1"></div>
        <div class="button-bottom-page-lg col-sm-1 col-xs-1"></div>
        <div class="button-bottom-page-lg col-sm-1 col-xs-1"></div>
        <div class="button-bottom-page-lg col-sm-1 col-xs-1"></div>
        <div class="spinner-button col-sm-1 col-xs-1">
            <?php $undo = Url::to('/integration'); ?>

            <?= Html::a('<span class="ladda-label">Отменить</span><span class="ladda-spinner"></span>', $undo, [
                'class' => 'btn darkblue widthe-100 hidden-md hidden-sm hidden-xs mt-ladda-btn ladda-button',
                'data-style' => 'expand-right',
            ]); ?>
            <?= Html::a('<i class="fa fa-reply fa-2x"></i>', $undo, [
                'class' => 'btn darkblue widthe-100 hidden-lg',
                'title' => 'Отменить',
            ]); ?>
        </div>
    </div>
</div>

<?php $form->end(); ?>

