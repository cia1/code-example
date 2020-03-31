<?php

use frontend\modules\integration\helpers\InsalesHelper;
use yii\web\View;

/**
 * @var View   $this
 * @var int    $count   Количество компаний клиента
 * @var string $company Название выбранной компании
 */
$this->title = 'Интеграция Эвотор';
?>

<div class="row small-boxes">

    <div class="col-md-6">
        <div class="portlet box darkblue">
            <div class="portlet-title">
                <div class="caption">Инструкция</div>
            </div>
            <div class="portlet-body">
                <p>Для интеграции выполните следующие шаги:</p>
                <ul>
                    <li>Зайдите в ваш личный кабинет InSales CMS и установите приложение &laquo;<?= InsalesHelper::APPLICATION_NAME ?>&raquo;</li>
                    <li>Далее в списке установленных приложений щёлкните мышкой на только что установленном приложении.</li>
                </ul>
                <?php if ($count > 1) { ?>
                    <p><b>Внимание</b>! Аккаунт InSales будет присоединён к выбранной в настоящий момент компании &laquo;<b><?= $company ?></b>&raquo;.</b></b></p>
                <?php } ?>
            </div>
        </div>
    </div>

</div>