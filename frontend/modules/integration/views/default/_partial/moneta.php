<?php
/**
 * Created by PhpStorm.
 * User: Валик
 * Date: 09.12.2019
 * Time: 23:24
 */

use philippfrenzel\yii2tooltipster\yii2tooltipster;
use yii\helpers\Url;
use common\widgets\Modal;
use yii\widgets\Pjax;
use common\components\helpers\Html;

/* @var $this yii\web\View
 * @var array $moneta
 * @var bool $canManage
 */

echo yii2tooltipster::widget([
    'options' => [
        'class' => '.tooltip2',
    ],
    'clientOptions' => [
        'theme' => ['tooltipster-kub'],
        'trigger' => 'hover',
        'zIndex' => 10000,
    ],
]);
?>
<div class="portlet box darkblue">
    <div class="portlet-title">
        <div class="caption">Moneta.ru</div>
        <span class="status-<?= $moneta['active'] ? 'active' : 'inactive' ?>"></span>
    </div>
    <div class="portlet-body">
        <p>Вы сможете выгружать операции за произвольные даты</p>
        <?php if ($canManage): ?>
            <?= Html::a('<i class="fa fa-download"></i> '
                . ($moneta['hasNotFinishedMonetaJob'] ? 'Загрузка выписки' : 'Загрузить выписку'), null, [
                'class' => 'btn btn__ins btn-sm default btn_marg_down green-haze btn-block '
                    . ($moneta['hasNotFinishedMonetaJob']
                        ? 'mt-ladda-btn ladda-button upload-moneta-process tooltip2'
                        : 'upload-moneta'),
                'disabled' => $moneta['hasNotFinishedMonetaJob'],
                'data' => [
                    'tooltip-content' => '#tooltip_upload-moneta-process',
                    'style' => 'expand-right',
                    'url' => $moneta['active']
                        ? Url::to(['/integration/moneta/upload'])
                        : Url::to(['/integration/moneta/connect']),
                ],
            ]) ?>
        <?php else: ?>
            <?= Html::a('<i class="fa fa-download"></i> Загрузить выписку', null, [
                'class' => 'btn btn__ins btn-sm default btn_marg_down green-haze btn-block',
                'data' => [
                    'toggle' => 'modal',
                    'target' => '#integration-not-allowed',
                ],
            ]) ?>
        <?php endif; ?>

        <?= $this->render('_link', [
            'text' => 'Отключить',
            'class' => 'btn btn__ins btn-sm default btn_marg_down red-haze btn-block disconnect-moneta',
            'style' => 'display: ' . ($moneta['active'] ? 'block' : 'none') . ';',
            'url' => Url::to('/integration/moneta/disconnect'),
            'canManage' => $canManage,
        ]) ?>
    </div>
</div>
<div class="tooltip_templates container-tooltip_templates">
    <div id="tooltip_upload-moneta-process" class="box-tooltip-templates">
        Операции загружаются. Это может занять несколько минут. После загрузки мы вас уведомим.
    </div>
</div>
<?php Modal::begin([
    'id' => 'upload-moneta-modal',
    'header' => '<h1>Запрос выписки из E-money</h1>',
]);
Pjax::begin([
    'id' => 'upload-moneta-pjax',
    'enablePushState' => false,
    'linkSelector' => false,
]);
Pjax::end();
Modal::end();

if ($moneta['hasNotFinishedMonetaJob']) {
    $this->registerJs('
        Ladda.create($(".upload-moneta-process")[0]).start();
    ');
}
?>
