<?php

use common\components\date\DateHelper;
use common\components\grid\GridView;
use frontend\components\StatisticPeriod;
use frontend\widgets\RangeButtonWidget;
use yii\bootstrap\ActiveForm;
use yii\bootstrap\Html;
use yii\web\View;
use frontend\modules\integration\models\VkDataProvider;
use yii\helpers\Url;

/**
 * @var View           $this
 * @var string         $backlink
 * @var string         $title
 * @var VkDataProvider $provider
 * @var array          $total
 */
$this->title = 'Интеграция ВКонтакте Ads';
?>

<?php if (isset($backlink) === true) { ?>
    <p><?= Html::a('Назад к списку', $backlink) ?></p>
<?php } ?>
<div class="portlet box">
    <h3 class="page-title yd-title">Статистика VK Ads</h3>
    <?php $form = ActiveForm::begin([
        'id' => 'get-yandex-direct-report',
    ]); ?>
    <div class="form-group">
        <?= $form->field($model, 'dateFrom', [
            'options' => [
                'class' => 'col-md-4',
            ],
            'wrapperOptions' => [
                'class' => 'yd-input',
            ],
            'template' => Yii::$app->params['formDatePickerTemplate'],
        ])->textInput([
            'class' => 'form-control date-picker min_wid_picker',
            'value' => DateHelper::format($model->dateFrom, DateHelper::FORMAT_USER_DATE, DateHelper::FORMAT_DATE),
        ])->label('с'); ?>

        <?= $form->field($model, 'dateTo', [
            'options' => [
                'class' => 'col-md-4',
                'style' => 'margin-right: 40px;',
            ],
            'wrapperOptions' => [
                'class' => 'yd-input',
            ],
            'template' => Yii::$app->params['formDatePickerTemplate'],
        ])->textInput([
            'class' => 'form-control date-picker min_wid_picker',
            'value' => DateHelper::format($model->dateTo, DateHelper::FORMAT_USER_DATE, DateHelper::FORMAT_DATE),
        ])->label('по') ?>

        <div class="btn-group pull-right title-buttons">
            <?= Html::submitButton('ВЫГРУЗИТЬ', [
                'class' => 'btn yellow',
            ]); ?>
        </div>
    </div>
    <?php ActiveForm::end(); ?>
    <div class="row" style="margin-top: 20px;margin-bottom: 25px;">
        <div class="col-md-9 col-sm-9"></div>
        <div class="col-md-3 col-sm-3">
            <?= RangeButtonWidget::widget(['cssClass' => 'doc-gray-button btn_select_days btn_row',]); ?>
        </div>
    </div>

    <?php if (isset($total) === true) { ?>
        <div class="row">
            <div class="col-md-12">
                <table class="table table-striped table-bordered table-hover" style="width: auto;">
                    <tbody>
                    <tr role="row">
                        <td>Всего</td>
                        <td class="text-left" style="font-weight: 600;">CPC</td>
                        <td class="text-left" style="font-weight: 600;">Расход</td>
                        <td class="text-left" style="font-weight: 600;">Impressions</td>
                        <td class="text-left" style="font-weight: 600;">Клики</td>
                        <td class="text-left" style="font-weight: 600;">Reach</td>
                    </tr>
                    <tr>
                        <td><a href="<?= Url::to('/integration/vk/by-days') ?>"><?= StatisticPeriod::getSessionName() ?></a></td>
                        <td><?= $total['cpc'] ?></td>
                        <td><?= $total['spent'] ?></td>
                        <td><?= $total['impressions'] ?></td>
                        <td><?= $total['clicks'] ?></td>
                        <td><?= $total['reach'] ?></td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    <?php } ?>

    <div class="portlet box darkblue">
        <div class="portlet-title"><?= $title ?></div>
        <div class="portlet-body accounts-list">
            <div class="table-container" style="">
                <?= GridView::widget([
                    'dataProvider' => $provider,
                    'tableOptions' => [
                        'class' => 'table table-striped table-bordered table-hover dataTable customers_table fix-thead',
                        'id' => 'datatable_ajax',
                        'aria-describedby' => 'datatable_ajax_info',
                        'role' => 'grid',
                    ],
                    'headerRowOptions' => [
                        'class' => 'heading',
                    ],
                    'options' => [
                        'class' => 'dataTables_wrapper dataTables_extended_wrapper',
                    ],
                    'pager' => [
                        'options' => [
                            'class' => 'pagination pull-right',
                        ],
                    ],
                    'layout' => $this->render('//layouts/grid/layout', ['totalCount' => $provider->totalCount]),
                    'columns' => $provider->columns(),
                ]); ?>
            </div>
        </div>

    </div>