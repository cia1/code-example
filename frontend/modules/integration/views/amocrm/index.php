<?php

use yii\helpers\Url;
use yii\web\View;

/**
 * @var View $this
 */
$this->title = 'Интеграция AMOcrm';
?>
<h1><?= $this->title ?></h1>
<ul>
    <li><a href="<?= Url::to('/integration/amocrm/lead') ?>">Сделки</a></li>
    <li><a href="<?= Url::to('/integration/amocrm/contact') ?>">Контакты</a></li>
    <li><a href="<?= Url::to('/integration/amocrm/company') ?>">Компании</a></li>
    <li><a href="<?= Url::to('/integration/amocrm/customer') ?>">Покупатели</a></li>
    <li><a href="<?= Url::to('/integration/amocrm/task') ?>">Задачи</a></li>
</ul>