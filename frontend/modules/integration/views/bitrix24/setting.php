<?php

use frontend\modules\integration\helpers\Bitrix24Helper;
use frontend\modules\integration\models\Bitrix24SettingsForm;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;
use yii\widgets\ActiveForm;

/**
 * @var View                 $this
 * @var Bitrix24SettingsForm $model
 */
$this->title = 'Интеграция Битрикс 24';
?>

<div class="row small-boxes">
    <?php $form = ActiveForm::begin([
        'enableAjaxValidation' => true,
        'enableClientValidation' => false,
        'action' => ['setting', 'backUrl' => 'bitrix24'],
        'options' => [
            'enctype' => 'multipart/form-data',
            'id' => 'form-update-company',
        ],
    ]); ?>

    <?= $form->errorSummary($model) ?>

    <div class="col-md-6">
        <div class="portlet box darkblue">
            <div class="portlet-title">
                <div class="caption">Подключение Битрикс 24</div>
            </div>
            <div class="portlet-body">
                <p>Для работы необходимо установить приложение &laquo;<?= Bitrix24Helper::APPLICATION_NAME ?>&raquo; из маркетплейс вашего Битрикс24.</p>
                <?= $form->field($model, 'host', [
                    'options' => [
                        'style' => '',
                    ],
                ]) ?>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="portlet box darkblue">
            <div class="portlet-title">
                <div class="caption">Чтобы подключить Битрикс24</div>
            </div>
            <div class="portlet-body">
                <p></p>
                <ul>
                    <li>Установить приложение &laquo;<?= Bitrix24Helper::APPLICATION_NAME ?>&raquo; из маркетплейс вашего Битрикс24</li>
                    <li>Введите адрес вашего Битрикс24 в поле на этой странице и нажмите &laquo;Подключить&raquo;.</li>
                </ul>
            </div>
        </div>
    </div>

</div>

<div class="form-actions">
    <div class="row action-buttons">
        <div class="spinner-button col-sm-1 col-xs-1">
            <?= Html::submitButton('<span class="ladda-label">Подключить</span><span class="ladda-spinner"></span>', [
                'class' => 'btn darkblue text-white widthe-100 hidden-md hidden-sm hidden-xs mt-ladda-btn _ladda-button',
                'data-style' => 'expand-right',
            ]); ?>
            <?= Html::submitButton('<i class="fa fa-floppy-o fa-2x"></i>', [
                'class' => 'btn darkblue text-white widthe-100 hidden-lg',
                'title' => 'Подключить',
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

