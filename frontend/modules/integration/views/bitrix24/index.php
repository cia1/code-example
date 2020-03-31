<?php

use yii\helpers\Url;
use yii\web\View;

/**
 * @var View $this
 */
$this->title = 'Интеграция Битрикс24';
?>
<h1><?= $this->title ?></h1>
<ul>
    <li><a href="<?= Url::to('/integration/bitrix24/catalog') ?>">Торговые каталоги</a></li>
    <li><a href="<?= Url::to('/integration/bitrix24/company') ?>">Компании</a></li>
    <li><a href="<?= Url::to('/integration/bitrix24/contact') ?>">Контакты</a></li>
    <li><a href="<?= Url::to('/integration/bitrix24/deal') ?>">Сделки</a></li>
    <li><a href="<?= Url::to('/integration/bitrix24/invoice') ?>">Счета</a></li>
</ul>