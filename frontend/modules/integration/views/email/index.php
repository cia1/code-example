<?php

use common\components\grid\GridView;
use common\components\helpers\Html;
use frontend\assets\IntegrationAsset;
use frontend\assets\InterfaceCustomAsset;
use frontend\modules\integration\controllers\EmailController;
use yii\data\ArrayDataProvider;
use yii\helpers\Html as yiiHtml;
use yii\helpers\Url;

/**
 * @var ArrayDataProvider $dataProvider
 * @var int[]             $withoutDetail Массив UID писем, для которых в кеше нет детальной информации
 */
InterfaceCustomAsset::register($this);
IntegrationAsset::register($this);
?>
<div class="portlet box">
    <div class="btn-group pull-right title-buttons">
        <a href="/integration/email/send" class="btn yellow" onclick="return sendMessagePanelOpen(undefined,'send')">
            <i class="fa fa-plus"></i> ОТПРАВИТЬ</a>
    </div>
    <h3 class="page-title">Входящая почта</h3>
</div>

<div class="portlet box darkblue">
    <div class="portlet-title">
        <div class="caption caption_for_input" style="width: 23%!important;">
            Входящие: <?= $dataProvider->totalCount ?>
        </div>
        <div id="mass_actions" class="actions joint-operations col-sm-5 pull-right"
             style="display: none;width: auto;padding-right: 0!important;padding-top: 8px!important;">
            <?= \yii\helpers\Html::a('<i class="glyphicon glyphicon-trash"></i> Удалить', '#many-delete', [
                'class' => 'btn btn-default btn-sm hidden-md hidden-sm hidden-xs',
                'data-toggle' => 'modal',
            ]); ?>
            <?= Html::a('<i class="glyphicon glyphicon-trash"></i>', '#many-delete', [
                'class' => 'btn btn-default btn-sm hidden-lg',
                'data-toggle' => 'modal',
            ]); ?>
        </div>
    </div>
    <div class="portlet-body accounts-list">
        <div class="table-container">
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'emptyText' => 'Новых писем нет',
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
                'layout' => $this->render('//layouts/grid/layout', ['totalCount' => $dataProvider->totalCount]),
                'columns' => [
                    [
                        'header' => Html::checkbox('', false, [
                            'class' => 'joint-operation-main-checkbox',
                        ]),
                        'headerOptions' => [
                            'class' => 'text-center pad0',
                            'width' => '5%',
                        ],
                        'contentOptions' => [
                            'class' => 'text-center pad0-l pad0-r',
                        ],
                        'format' => 'raw',
                        'value' => function ($data) {
                            return Html::checkbox('Imap[' . $data['id'] . '][checked]', false, [
                                'class' => 'joint-operation-checkbox',
                            ]);
                        },
                    ],
                    [
                        'attribute' => 'from',
                        'format' => 'raw',
                        'label' => 'От кого',
                        'value' => function (array $item) {
                            return Html::a('<span title="' . $item['fromEmail'] . '">' . $item['fromName'] . '</span>', '/integration/email/' . $item['id'],
                                ['onclick' => 'return sendMessagePanelOpen(' . $item['id'] . ')']);
                        },
                    ],
                    [
                        'attribute' => 'subject',
                        'label' => 'Тема',
                        'format' => 'raw',
                        'value' => function (array $item) {
                            $s = Html::a($item['subject'], '/integration/email/' . $item['id'], ['onclick' => 'return sendMessagePanelOpen(' . $item['id'] . ')']);
                            if (isset($item['detail']) === false) {
                                return $s;
                            }
                            if (mb_strlen($item['textPlain']) > EmailController::MESSAGE_IN_TABLE_LENGTH) {
                                $item['textPlain'] = mb_substr($item['textPlain'], 0, EmailController::MESSAGE_IN_TABLE_LENGTH) . '...';
                            }
                            $s .= '<p>' . $item['textPlain'] . '</p>';
                            return $s;
                        },
                    ],
                    [
                        'attribute' => 'date',
                        'label' => 'Дата и время',
                        'headerOptions' => [
                            'class' => 'sorting',
                            'width' => '10%',
                        ],
                    ],
                ],
            ]) ?>
        </div>
    </div>
</div>

<?php if (count($withoutDetail) > 0) { ?>
    <script>
        $(document).ready(function () {
            loadLetterDetails(<?=json_encode($withoutDetail)?>);
        });
    </script>
<?php } ?>

<div id="many-delete" class="confirm-modal fade modal" role="dialog"
     tabindex="-1" aria-hidden="true"
     style="display: none; margin-top: -51.5px;">
    <div class="modal-dialog ">
        <div class="modal-content">
            <div class="modal-body">
                <div class="form-body">
                    <div class="row">Вы уверены, что хотите удалить выбранные письма?</div>
                </div>
                <div class="form-actions row">
                    <div class="col-xs-6">
                        <?= yiiHtml::a('<span class="ladda-label">Да</span><span class="ladda-spinner"></span>', null, [
                            'class' => 'btn darkblue pull-right modal-many-delete ladda-button',
                            'data-url' => Url::to('/integration/email/many-delete'),
                            'data-style' => 'expand-right',
                            'onclick' => 'setTimeout(function() { location.reload() }, 1500)',
                        ]); ?>
                    </div>
                    <div class="col-xs-6">
                        <button type="button" class="btn darkblue" data-dismiss="modal">НЕТ</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
