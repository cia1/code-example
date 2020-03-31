<?php

use common\components\grid\GridView;
use frontend\assets\IntegrationAsset;
use yii\data\ActiveDataProvider;
use yii\helpers\Url;
use yii\web\View;

/**
 * @var View               $this
 * @var string             $entityName
 * @var ActiveDataProvider $dataProvider
 * @var array              $columns
 */
$this->title = 'Интеграция InSales CMS: ' . $entityName;
IntegrationAsset::register($this);
?>
<p><a href="<?= Url::to('/integration/insales') ?>">Назад к списку</a></p>
<div class="portlet box darkblue">
    <div class="portlet-title"><?= $this->title ?></div>
    <div class="portlet-body accounts-list">
        <div class="table-container" style="">
            <?= /** @noinspection PhpUnhandledExceptionInspection */
            GridView::widget([
                'dataProvider' => $dataProvider,
                'tableOptions' => [
                    'class' => 'table table-striped table-bordered table-hover dataTable customers_table fix-thead',
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
                'columns' => $model->gridColumns,
            ]); ?>
        </div>
    </div>
</div>