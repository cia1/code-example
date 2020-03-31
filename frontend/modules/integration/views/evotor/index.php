<?php

use yii\helpers\Url;
use yii\web\View;

/**
 * @var View $this
 */
$this->title = 'Интеграция Эвотор';
?>
<h1><?= $this->title ?></h1>
<ul>
    <li><a href="<?= Url::to('/integration/evotor/store') ?>">Магазины</a></li>
    <li><a href="<?= Url::to('/integration/evotor/device') ?>">Терминалы</a></li>
    <li><a href="<?= Url::to('/integration/evotor/employee') ?>">Сотрудники</a></li>
    <li><a href="<?= Url::to('/integration/evotor/receipt') ?>">Чеки</a></li>
    <li><a href="<?= Url::to('/integration/evotor/product') ?>">Товары</a></li>
</ul>