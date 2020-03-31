<?php
/**
 * Created by PhpStorm.
 * User: Валик
 * Date: 14.01.2020
 * Time: 22:23
 */

use yii\helpers\Url;

/* @var $this yii\web\View
 * @var array $googleAds
 * @var bool $canManage
 */
?>
<div class="portlet box darkblue">
    <div class="portlet-title">
        <div class="caption">Google Ads</div>
        <span class="<?= $googleAds['active'] ? 'status-active' : 'status-inactive' ?>"></span>
    </div>
    <div class="portlet-body">
        <p>Вы сможете выгружать статистику за произвольные даты</p>
        <?= $this->render('_link', [
            'text' => $googleAds['active'] ? 'Данные' : 'Подключить',
            'class' => 'btn btn__ins btn-sm default btn_marg_down green-haze btn-block',
            'url' => Url::to('/reports/google-ads/index'),
            'canManage' => $canManage,
        ]) ?>
        <?php if ($googleAds['active']): ?>
            <?= $this->render('_link', [
                'text' => 'Отключить',
                'class' => 'btn btn__ins btn-sm default btn_marg_down red-haze btn-block',
                'url' => Url::to('/integration/google-ads/disconnect'),
                'canManage' => $canManage,
            ]) ?>
        <?php endif; ?>
    </div>
</div>