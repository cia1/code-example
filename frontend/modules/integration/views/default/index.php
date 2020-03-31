<?php

use frontend\assets\IntegrationAsset;
use yii\helpers\Url;
use yii\web\View;
use common\models\employee\Employee;

/**
 * @var View        $this
 * @var Employee    $user
 * @var bool        $canManage
 * @var string|null $popupMessage Сообщение, которое нужно отобразить во всплывающем окне
 * @var array       $amocrm       Настройки интеграции: AMOcrm
 * @var array       $email        Настройки интеграции: электронная почта
 * @var array       $facebook     Настройки интеграции: Facebook
 * @var array       $evotor       Настройки интеграции: Эвотор
 * @var array       $moneta       Настройки интеграции: Moneta
 * @var array       $vk           Настройки интеграции: Вконтакте
 * @var array       $unisender    Настройки интеграции: Unisender
 * @var array       $insales      Настройки интеграции: In Sale CMS
 * @var array       $bitrix24     Настройки интеграции (имя хоста): Битрикс 24
 * @var array       $googleAds    Настройки интеграции: Google Ads
 * @var array       $yandexDirect Настройки интеграции: Yandex Direct
 * @var array       $dreamkas     Настройки интеграции Dreamkas
 */

$this->title = 'Настройка интеграций';
IntegrationAsset::register($this);
?>
    <div class="row small-boxes integration-row">

        <div class="col-md-4">
            <div class="portlet box darkblue">
                <div class="portlet-title">
                    <div class="caption">Почта</div>
                    <span class="status-<?= $email['active'] === true ? 'active' : 'inactive' ?>"></span>
                </div>
                <div class="portlet-body">
                    <p>Вы сможете принимать e-mail сообщения и отправлять их из КУБ в режиме одного окна</p>
                    <?= $this->render('_partial/_link', [
                        'text' => 'Настройки',
                        'class' => 'btn btn__ins btn-sm default btn_marg_down green-haze btn-block',
                        'url' => Url::to('integration/email/setting'),
                        'canManage' => $canManage,
                    ]) ?>
                    <?php if ($email['configured'] === true) { ?>
                        <?= $this->render('_partial/_link', [
                            'text' => 'Отключить',
                            'class' => 'btn btn__ins btn-sm default btn_marg_down red-haze btn-block',
                            'url' => Url::to('integration/email/disconnect'),
                            'canManage' => $canManage,
                        ]) ?>
                    <?php } ?>
                </div>
            </div>
        </div>
        <?php /*
    <div class="col-md-4">
        <div class="portlet box darkblue">
            <div class="portlet-title">
                <div class="caption">Яндекс Директ</div>
                <span class="<?= $user->yandex_access_token === null ? 'status-inactive' : 'status-active' ?>"></span>
            </div>
            <div class="portlet-body">
                <p>Вы сможете выгружать статистику за произвольные даты</p>
                <?= $this->render('_partial/_link', [
                    'text' => 'Настройки',
                    'class' => 'btn btn__ins btn-sm default btn_marg_down green-haze btn-block',
                    'url' => Url::to('/reports/yandex-direct/index'),
                    'canManage' => $canManage,
                ]) ?>
                <?php if ($user->yandex_access_token !== null): ?>
                    <?= $this->render('_partial/_link', [
                        'text' => 'Отключить',
                        'class' => 'btn btn__ins btn-sm default btn_marg_down red-haze btn-block',
                        'url' => Url::to('/integration/yandex-direct/disconnect'),
                        'canManage' => $canManage,
                    ]) ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
*/ ?>
        <div class="col-md-4">
            <div class="portlet box darkblue">
                <div class="portlet-title">
                    <div class="caption">AMOcrm</div>
                    <span class="status-<?= $amocrm['active'] === true ? 'active' : 'inactive' ?>"></span>
                </div>
                <div class="portlet-body">
                    <p></p>
                    <?php if ($amocrm['configured'] === true): ?>
                        <?= $this->render('_partial/_link', [
                            'text' => 'Открыть',
                            'class' => 'btn btn__ins btn-sm default btn_marg_down green-haze btn-block',
                            'url' => Url::to('integration/amocrm'),
                            'canManage' => $canManage,
                        ]) ?>
                    <?php endif; ?>
                    <?= $this->render('_partial/_link', [
                        'text' => 'Настройки',
                        'class' => 'btn btn__ins btn-sm default btn_marg_down green-haze btn-block',
                        'url' => Url::to('integration/amocrm/setting'),
                        'canManage' => $canManage,
                    ]) ?>
                    <?php if ($amocrm['configured'] === true): ?>
                        <?= $this->render('_partial/_link', [
                            'text' => 'Отключить',
                            'class' => 'btn btn__ins btn-sm default btn_marg_down red-haze btn-block',
                            'url' => Url::to('integration/amocrm/disconnect'),
                            'canManage' => $canManage,
                        ]) ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="portlet box darkblue">
                <div class="portlet-title">
                    <div class="caption">Facebook</div>
                    <span class="status-<?= $facebook['active'] === true ? 'active' : 'inactive' ?>"></span>
                </div>
                <div class="portlet-body">
                    <p></p>
                    <?= $this->render('_partial/_link', [
                        'text' => ($facebook['active'] === true ? 'Открыть' : 'Подключить'),
                        'class' => 'btn btn__ins btn-sm default btn_marg_down green-haze btn-block',
                        'url' => Url::to('integration/facebook'),
                        'canManage' => $canManage,
                    ]) ?>
                    <?php if ($facebook['active'] === true) {
                        echo $this->render('_partial/_link', [
                            'text' => 'Отключить',
                            'class' => 'btn btn__ins btn-sm default btn_marg_down red-haze btn-block',
                            'url' => Url::toRoute(['facebook/disconnect']),
                            'canManage' => $canManage
                        ]);
                    } ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="portlet box darkblue">
                <div class="portlet-title">
                    <div class="caption">ВКонтакте</div>
                    <span class="status-<?= $vk['active'] === true ? 'active' : 'inactive' ?>"></span>
                </div>
                <div class="portlet-body">
                    <p></p>
                    <?php if ($vk['configured'] === true): ?>
                        <?= $this->render('_partial/_link', [
                            'text' => 'Данные',
                            'class' => 'btn btn__ins btn-sm default btn_marg_down green-haze btn-block',
                            'url' => Url::to('integration/vk'),
                            'canManage' => $canManage,
                        ]) ?>
                    <?php else: ?>
                        <?= $this->render('_partial/_link', [
                            'text' => 'Подключить',
                            'class' => 'btn btn__ins btn-sm default btn_marg_down green-haze btn-block',
                            'url' => Url::to('integration/vk/setting'),
                            'canManage' => $canManage,
                        ]) ?>
                    <?php endif; ?>
                    <?php if ($vk['configured'] === true): ?>
                        <?= $this->render('_partial/_link', [
                            'text' => 'Отключить',
                            'class' => 'btn btn__ins btn-sm default btn_marg_down red-haze btn-block',
                            'url' => Url::to('integration/vk/disconnect'),
                            'canManage' => $canManage,
                        ]) ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="portlet box darkblue">
                <div class="portlet-title">
                    <div class="caption">Google Ads</div>
                    <span class="<?= $googleAds['active'] ? 'status-active' : 'status-inactive' ?>"></span>
                </div>
                <div class="portlet-body">
                    <p>Вы сможете выгружать статистику за произвольные даты</p>
                    <?= $this->render('_partial/_link', [
                        'text' => $googleAds['active'] ? 'Данные' : 'Подключить',
                        'class' => 'btn btn__ins btn-sm default btn_marg_down green-haze btn-block',
                        'url' => $googleAds['active'] ? Url::to('/reports/google-ads/index') : Url::to('/reports/google-ads/connect'),
                        'canManage' => $canManage,
                    ]) ?>
                    <?php if ($googleAds['active']): ?>
                        <?= $this->render('_partial/_link', [
                            'text' => 'Отключить',
                            'class' => 'btn btn__ins btn-sm default btn_marg_down red-haze btn-block',
                            'url' => Url::to('/reports/google-ads/disconnect'),
                            'canManage' => $canManage,
                        ]) ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="portlet box darkblue">
                <div class="portlet-title">
                    <div class="caption">Эвотор</div>
                    <span class="status-<?= $evotor['active'] === true ? 'active' : 'inactive' ?>"></span>
                </div>
                <div class="portlet-body">
                    <p></p>
                    <?php if ($evotor['configured'] === true): ?>
                        <?= $this->render('_partial/_link', [
                            'text' => 'Открыть',
                            'class' => 'btn btn__ins btn-sm default btn_marg_down green-haze btn-block',
                            'url' => Url::to('integration/evotor'),
                            'canManage' => $canManage,
                        ]) ?>
                        <?= $this->render('_partial/_link', [
                            'text' => 'Отключить',
                            'class' => 'btn btn__ins btn-sm default btn_marg_down red-haze btn-block',
                            'url' => Url::to('integration/evotor/disconnect'),
                            'canManage' => $canManage,
                        ]) ?>
                    <?php else: ?>
                        <?= $this->render('_partial/_link', [
                            'text' => 'Подключить',
                            'class' => 'btn btn__ins btn-sm default btn_marg_down green-haze btn-block',
                            'url' => Url::to('integration/evotor/setting'),
                            'canManage' => $canManage,
                        ]) ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <?= $this->render('_partial/moneta', [
                'moneta' => $moneta,
                'canManage' => $canManage,
            ]) ?>
        </div>

        <div class="col-md-4">
            <div class="portlet box darkblue">
                <div class="portlet-title">
                    <div class="caption">Unisender</div>
                    <span class="status-<?= $unisender['active'] ? 'active' : 'inactive' ?>"></span>
                </div>
                <div class="portlet-body">
                    <p>Вы сможете выгружать письма за произвольные даты</p>
                    <?= $this->render('_partial/_link', [
                        'text' => 'Настройки',
                        'class' => 'btn btn__ins btn-sm default btn_marg_down green-haze btn-block',
                        'url' => $unisender['active'] ? Url::to('/integration/unisender/messages') : Url::to('/integration/unisender/connect'),
                        'canManage' => $canManage,
                    ]) ?>
                    <?php if ($unisender['active']): ?>
                        <?= $this->render('_partial/_link', [
                            'text' => 'Отключить',
                            'class' => 'btn btn__ins btn-sm default btn_marg_down red-haze btn-block',
                            'url' => Url::to('/integration/unisender/disconnect'),
                            'canManage' => $canManage,
                        ]) ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="portlet box darkblue">
                <div class="portlet-title">
                    <div class="caption">In Sales CMS</div>
                    <span class="status-<?= $insales['active'] ? 'active' : 'inactive' ?>"></span>
                </div>
                <div class="portlet-body">
                    <p></p>
                    <?php if ($insales['configured'] === true): ?>
                        <?= $this->render('_partial/_link', [
                            'text' => 'Открыть',
                            'class' => 'btn btn__ins btn-sm default btn_marg_down green-haze btn-block',
                            'url' => Url::to('integration/insales'),
                            'canManage' => $canManage,
                        ]) ?>
                        <?= $this->render('_partial/_link', [
                            'text' => 'Отключить',
                            'class' => 'btn btn__ins btn-sm default btn_marg_down red-haze btn-block',
                            'url' => Url::to('integration/insales/disconnect'),
                            'canManage' => $canManage,
                        ]) ?>
                    <?php else: ?>
                        <?= $this->render('_partial/_link', [
                            'text' => 'Подключить',
                            'class' => 'btn btn__ins btn-sm default btn_marg_down green-haze btn-block',
                            'url' => Url::to('integration/insales/setting'),
                            'canManage' => $canManage,
                        ]) ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="portlet box darkblue">
                <div class="portlet-title">
                    <div class="caption">Битрикс 24</div>
                    <span class="status-<?= $bitrix24['active'] ? 'active' : 'inactive' ?>"></span>
                </div>
                <div class="portlet-body">
                    <p></p>
                    <?php if ($bitrix24['configured'] === true): ?>
                        <?= $this->render('_partial/_link', [
                            'text' => 'Открыть',
                            'class' => 'btn btn__ins btn-sm default btn_marg_down green-haze btn-block',
                            'url' => Url::to('integration/bitrix24'),
                            'canManage' => $canManage,
                        ]) ?>
                        <?= $this->render('_partial/_link', [
                            'text' => 'Отключить',
                            'class' => 'btn btn__ins btn-sm default btn_marg_down red-haze btn-block',
                            'url' => Url::to('integration/bitrix24/disconnect'),
                            'canManage' => $canManage,
                        ]) ?>
                    <?php else: ?>
                        <?= $this->render('_partial/_link', [
                            'text' => 'Подключить',
                            'class' => 'btn btn__ins btn-sm default btn_marg_down green-haze btn-block',
                            'url' => Url::to('integration/bitrix24/setting'),
                            'canManage' => $canManage,
                        ]) ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="portlet box darkblue">
                <div class="portlet-title">
                    <div class="caption">Dreamkas</div>
                    <span class="status-<?= $dreamkas['active'] ? 'active' : 'inactive' ?>"></span>
                </div>
                <div class="portlet-body">
                    <p></p>
                    <?php if ($dreamkas['configured'] === true): ?>
                        <?= $this->render('_partial/_link', [
                            'text' => 'Открыть',
                            'class' => 'btn btn__ins btn-sm default btn_marg_down green-haze btn-block',
                            'url' => Url::to('integration/dreamkas'),
                            'canManage' => $canManage,
                        ]) ?>
                        <?= $this->render('_partial/_link', [
                            'text' => 'Отключить',
                            'class' => 'btn btn__ins btn-sm default btn_marg_down red-haze btn-block',
                            'url' => Url::to('integration/dreamkas/disconnect'),
                            'canManage' => $canManage,
                        ]) ?>
                    <?php else: ?>
                        <?= $this->render('_partial/_link', [
                            'text' => 'Подключить',
                            'class' => 'btn btn__ins btn-sm default btn_marg_down green-haze btn-block',
                            'url' => Url::to('integration/dreamkas/setting'),
                            'canManage' => $canManage,
                        ]) ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>

    <div id="integration-not-allowed" class="fade modal" role="dialog" tabindex="-1" aria-hidden="true"
         style="display: none; margin-top: -45px;">
        <div class="modal-dialog ">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-body" style="margin-bottom: 15px;text-align: center;font-size: 16px;">
                        <div class="row">
                            Вам не доступна данная функция, обратитесь к вашему Руководителю.
                        </div>
                    </div>
                    <div class="form-actions row">
                        <div class="col-xs-12 text-center">
                            <button type="button" data-dismiss="modal" class="btn darkblue"
                                    style="width: 80px;color: white;">
                                ОК
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php if ($popupMessage !== null) { ?>
    <a id="popupMessageA" data-toggle="modal" data-target="#popupMessage">Открыть!</a>

    <div id="popupMessage" class="fade modal" role="dialog" tabindex="-1" aria-hidden="true" style="display: none; margin-top: -45px;">
        <div class="modal-dialog ">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-body" style="margin-bottom: 15px;text-align: center;font-size: 16px;">
                        <div class="row"><?= $popupMessage ?></div>
                    </div>
                    <div class="form-actions row">
                        <div class="col-xs-12 text-center">
                            <button type="button" data-dismiss="modal" class="btn darkblue"
                                    style="width: 80px;color: white;">ОК
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        jQuery(document).ready(function () {
            setTimeout(function () {
                jQuery('#popupMessageA').click();
            }, 500);
        });
    </script>
<?php }