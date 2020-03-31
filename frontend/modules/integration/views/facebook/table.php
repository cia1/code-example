<?php

use common\components\date\DateHelper;
use common\components\grid\GridView;
use frontend\modules\integration\models\DateFromToFilterForm;
use frontend\widgets\RangeButtonWidget;
use yii\bootstrap\ActiveForm;
use yii\bootstrap\Html;
use yii\web\View;
use frontend\modules\integration\models\FacebookDataProvider;

/**
 * @var View                 $this
 * @var string               $title
 * @var DateFromToFilterForm $model
 * @var FacebookDataProvider $provider
 */
$this->title = 'Интеграция Facebook Ads';
?>

<div class="portlet box">
    <h3 class="page-title yd-title"><?= $title ?></h3>

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

    <div class="row">
        <div class="col-md-12">
            <table class="table table-striped table-bordered table-hover" style="width: auto;">
                <tbody>
                <tr role="row">
                    <td class="text-left" style="font-weight: 600;">Показатель:</td>
                    <td class="text-left" style="font-weight: 600;">CPP</td>
                    <td class="text-left" style="font-weight: 600;">CPM</td>
                    <td class="text-left" style="font-weight: 600;">Клики</td>
                    <td class="text-left" style="font-weight: 600;">Расход</td>
                    <td class="text-left" style="font-weight: 600;">Reach</td>
                    <td class="text-left" style="font-weight: 600;">Impressions</td>
                </tr>
                <tr>
                    <td style="font-weight:bold;">Всего:</td>
                    <td><?= $total['cpp'] ?></td>
                    <td><?= $total['cpm'] ?></td>
                    <td><?= $total['clicks'] ?></td>
                    <td><?= $total['spend'] ?></td>
                    <td><?= $total['reach'] ?></td>
                    <td><?= $total['impressions'] ?></td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="portlet box darkblue">
        <div class="portlet-title"></div>
        <div class="portlet-body accounts-list">
            <div class="table-container" style="">
                <?= GridView::widget([
                    'dataProvider' => $provider,
//                    'filterModel' => $searchModel,
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
                    'columns' => [
                        [
                            'attribute' => 'campaign_name',
                            'label' => 'Название',
                        ],
                        [
                            'attribute' => 'cpp',
                            'label' => 'CPP',
                        ],
                        [
                            'attribute' => 'cpm',
                            'label' => 'CPM',
                        ],
                        [
                            'attribute' => 'clicks',
                            'label' => 'Клики',
                        ],
                        [
                            'attribute' => 'spend',
                            'label' => 'Расход',
                        ],
                        [
                            'attribute' => 'account_currency',
                            'label' => 'Валюта',
                        ],
                        'reach',
                        [
                            'attribute' => 'actions',
                            'format' => 'raw',
                            'value' => function ($item) {
                                $value = [];
                                if (isset($item['actions']) === true) {
                                    foreach ($item['actions'] as $action) {
                                        $value[] = $action['action_type'] . '=' . $action['value'];
                                    }
                                }
                                if (isset($item['video_play_actions']) === true) {
                                    foreach ($item['video_play_actions'] as $action) {
                                        $value[] = 'video_play_' . $action['action_type'] . '=' . $action['value'];
                                    }
                                }
                                if (isset($item['video_avg_time_watched_actions']) === true) {
                                    foreach ($item['video_avg_time_watched_actions'] as $action) {
                                        $value[] = 'video_avg_time_' . $action['action_type'] . '=' . $action['value'];
                                    }
                                }
                                return implode('<br>', $value);
                            },
                        ],
                        'impressions',
                        [
                            'headerOptions' => [
                                'width' => '70px',
                            ],
                            'contentOptions' => [
                                'align' => 'center',
                            ],
                            'format' => 'raw',
                            'label' => 'Период',
                            'value' => function ($item) {
                                return $item['date_start'] . '<br>-<br>' . $item['date_stop'];
                            },
                        ],
                    ],
                ]); ?>
            </div>
        </div>

    </div>
