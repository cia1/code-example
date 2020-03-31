<?php

use yii\helpers\Url;
use yii\web\View;

/**
 * @var View $this
 */
$this->title = 'Интеграция InSales CMS';
?>
<h1><?= $this->title ?></h1>
<ul>
    <li><a href="<?= Url::to('/integration/insales/client') ?>">Клиенты</a></li>
    <li><a href="<?= Url::to('/integration/insales/product') ?>">Товары</a></li>
    <li><a href="<?= Url::to('/integration/insales/order') ?>">Заказы</a></li>
</ul>