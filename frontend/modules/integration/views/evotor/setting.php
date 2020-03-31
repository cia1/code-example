<?php

use frontend\modules\integration\helpers\EvotorHelper;
use yii\web\View;

/**
 * @var View   $this
 * @var int    $count   Количество зарегистрированных пользователем компаний
 * @var string $company Название текущей компании
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
                    <li>Зайдите в ваш личный кабинет Эвотор и установите приложение &laquo;<?= EvotorHelper::APPLICATION_NAME ?>&raquo;</li>
                    <li>Приложение спросит логин и пароль, введите данные, используемые для доступа в КУБ-24.</li>
                </ul>
                <?php if ($count > 1) { ?>
                    <hr/>
                    <p><b>Внимание!</b> Аккаунт Эвотор будет подключён к компании &laquo;<b><?= $company ?></b>&raquo;. Выберите другую компанию, если это требуется.</p>
                <?php } ?>
            </div>
        </div>
    </div>

</div>