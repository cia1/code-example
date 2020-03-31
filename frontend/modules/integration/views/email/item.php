<?php

use common\components\helpers\Html;
use faryshta\widgets\JqueryTagsInput;
use frontend\modules\integration\controllers\EmailController;
use frontend\modules\integration\helpers\ImapHelper;
use frontend\modules\integration\models\EmailLetterForm;
use yii\bootstrap\ActiveForm;
use yii\helpers\Url;
use yii\web\JsExpression;

/**
 * @var EmailLetterForm $letterForm
 * @var string|null     $date     Дата отправки
 * @var int             $uid      UID письма
 * @var string          $action   Запрошенное действие контроллера
 * @var array[]         $actions  Массив доступных действий: [0] - action, [1] - надпись на кнопке
 * @var string[]        $readonly Список полей, которые нельзя редактировать
 */
?>
<span class="header"><?= $action === EmailController::ACTION_SEND ? 'Отправка письма' : 'Входящее письмо' ?></span>

<?php $form = ActiveForm::begin([
    'action' => Url::to('/integration/email/post'),
    'enableAjaxValidation' => true,
    'enableClientValidation' => false,
    'options' => ['name' => 'sendEMail'],
]); ?>
<input type="hidden" name="action" value="<?= $action ?>"/>
<input type="hidden" name="uid" value="<?= $uid ?>"/>
<?= $form->errorSummary($letterForm); ?>

<?php if ($date !== null) { ?>
    <div class="form-block">
        <div class="email-label">Получено:</div>
        <div class="form-group field-message-from required"><?= $date ?></div>
    </div>
<?php } ?>


<div class="form-block" data-tooltip-content="#send-from-tooltip">
    <div class="email-label">От кого:</div>
    <?= $form->field($letterForm, 'from')->textInput([
        'class' => 'form-control input-sm',
        'id' => 'message-from',
        'aria-required' => true,
        'readonly' => in_array('from', $readonly),
    ])->label(false) ?>
</div>


<?php if (in_array('to', $readonly) === true) { ?>
    <div class="form-block">
        <div class="email-label">Кому:</div>
        <?= $form->field($letterForm, 'to')->textInput([
            'class' => 'form-control input-sm',
            'id' => 'message-to',
            'aria-required' => true,
            'readonly' => true,
        ])->label(false); ?>
    </div>
<?php } else { ?>
    <div class="block-to-email">
        <div class="email-label">Кому:</div>
        <div class="dropdown-email">
            <?= $form->field($letterForm, 'to', [
                'options' => [
                    'class' => 'form-group form-md-line-input form-md-floating-label field-message-to',
                    'style' => 'width: 96%;',
                ],
            ])->widget(JqueryTagsInput::className(), [
                'clientOptions' => [
                    'width' => '100%',
                    'defaultText' => '',
                    'removeWithBackspace' => true,
                    'onAddTag' => new JsExpression("function (val) {
                            var tagInput = $('#emailletterform-to');
                            var reg = /^([A-Za-zА-Яа-я0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,15})$/;
                            if (reg.test(val) == false) {
                                tagInput.removeTag(val);
                            } else {
                                $('.who-send-container .container-who-send-label').each(function () {
                                    var sendToCheckBox = $(this).find('.checker input');
                                    if (sendToCheckBox.data('value') == val) {
                                        if (!sendToCheckBox.is(':checked')) {
                                            sendToCheckBox.click();
                                        }
                                    }
                                });
                            }
                            if ($('#emailletterform-to').val() == '') {
                                $('#emailletterform-to_tagsinput #emailletterform-to_tag')
                                .attr('placeholder', 'Укажите e-mail')
                                .addClass('visible');
                            } else {
                                $('#emailletterform-to_tagsinput #emailletterform-to_tag')
                                .removeAttr('placeholder')
                                .removeClass('visible');
                            }
                        }"),
                    'onRemoveTag' => new JsExpression("function (val) {
                            $('.who-send-container .container-who-send-label').each(function () {
                                sendToCheckBox = $(this).find('.checker input');
                                if (sendToCheckBox.data('value') == val) {
                                    if (sendToCheckBox.is(':checked')) {
                                        sendToCheckBox.click();
                                    }
                                }
                            });
                            if ($('#emailletterform-to').val() == '') {
                                $('#emailletterform-to_tagsinput #emailletterform-to_tag')
                                .attr('placeholder', 'Укажите e-mail')
                                .addClass('visible');
                            } else {
                                $('#emailletterform-to_tagsinputemailletterform-to_tagsinput #emailletterform-to_tag')
                                .removeAttr('placeholder')
                                .removeClass('visible');
                            }
                        }"),
                ],
            ])->label(false); ?>
        </div>
    </div>
<?php } ?>


<div class="form-block block-subject-email">
    <div class="email-label">Тема:</div>
    <?= $form->field($letterForm, 'subject', [
        'options' => [
            'class' => 'form-group form-md-line-input form-md-floating-label field-message-subject',
            'style' => 'width: 90%;',
        ],
    ])->textInput([
        'class' => 'form-control input-sm edited',
        'id' => 'message-subject',
        'aria-required' => true,
        'readonly' => in_array('subject', $readonly),
        'placeholder'=>'Тема письма'

    ])->label(false); ?>
</div>


<?php if ($action === EmailController::ACTION_SEND) { ?>
    <div class="row block-files-email" style="padding-bottom: 10px;margin-left: 0;margin-right: 0;">
        <span class="upload-file" data-url="<?= Url::to(['/email/upload-email-file']); ?>"
              data-csrf-parameter="<?= Yii::$app->request->csrfParam; ?>"
              data-csrf-token="<?= Yii::$app->request->csrfToken; ?>">
            <span class="icon icon-paper-clip"></span>
            <span class="upload-file-email border-b">Прикрепить файл</span>
        </span>
        <div id="file-ajax-loading" style="display: none;">
            <img src="/img/loading.gif">
        </div>
    </div>

    <div class="row email-uploaded-files" style="<?= empty($permanentFiles) ? 'display: none;' : null; ?>">
        <?= Html::activeHiddenInput($letterForm, 'attachments',[
                'value'=>''
        ]); ?>
        <div class="one-file col-md-6 template">
            <span class="file-name"></span>
            <span class="file-size"></span>
            <div class="file-actions">
                <?= Html::a('<span class="glyphicon glyphicon-eye-open view-file"></span>', null, [
                    'target' => '_blank',
                    'class' => 'download-file',
                    'style' => 'display: none;',
                ]); ?>
                <span class="glyphicon glyphicon-trash delete-file"></span>
            </div>
        </div>
    </div>

<?php } ?>


<div class="email_text_input">
    <?php if (in_array('body', $readonly) === true) { ?>
        <div class="form-group field-message-to required">
            <?= $letterForm->body ?>
        </div>
    <?php } else {
        echo $form->field($letterForm, 'body')->textarea([
            'rows' => 7,
            'style' => 'padding: 10px 0; width: 100%; border: 0;border-bottom:1px solid #e5e5e5;overflow-y: auto;',
        ])->label(false);
    } ?>
</div>


<?php if (($action === EmailController::ACTION_VIEW || $action === EmailController::ACTION_CC) && $letterForm->attachments) { ?>
    <div class="row email-uploaded-files" style="margin-top:25px;">
        <input type="hidden" id="invoicesendform-sendemailfiles" name="InvoiceSendForm[sendEmailFiles]" value="12, 13">
        <?php foreach ($letterForm->attachments as $item) { ?>
            <div class="one-file col-md-6" data-id="12">
                <img src="<?= ImapHelper::iconByFileName($item['name']) ?>" class="preview-img" alt="">
                <span class="file-name" title="<?= $item['name'] ?>"><?= $item['name'] ?></span>
                <span class="file-size"><?= Yii::$app->formatter->asShortSize($item['size']) ?></span>
                <div class="file-actions">
                    <a class="download-file" target="_blank" style="" href="<?= Url::to(['/integration/email/' . $uid . '/attachment', 'attachId' => $item['id'], 'file' => $item['name']]) ?>">
                        <span class="glyphicon glyphicon-eye-open view-file"></span>
                    </a>
                    <?php /* <span class="glyphicon glyphicon-trash delete-file" data-url="/email/delete-email-file?id=13"></span> */ ?>
                </div>

            </div>
        <?php } ?>
    </div>
<?php } ?>


<div class="form-actions" style="z-index: 999;">
    <div class="row action-buttons">
        <div class="col-sm-5 col-xs-5" style="width: 14%;">
            <div class="btn btn-group dropup">
                <?= Html::submitButton('<span class="ladda-label">' . $actions[0][1] . '</span><span class="ladda-spinner"></span>', [
                    'class' => 'btn darkblue text-white mt-ladda-btn ladda-button btn-danger',
                    'data-style' => 'expand-right',
                    'data-action' => $actions[0][0],
                ]); ?>
                <button type="button" class="btn darkblue text-white btn-danger dropdown-toggle dropdown-toggle-split" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <span class="sr-only">Toggle Dropdown</span>
                </button>
                <div class="dropdown-menu">
                    <?php for ($i = 0, $cnt = count($actions);
                               $i < $cnt;
                               $i++) { ?>
                        <a class="dropdown-item" href="#" onclick="setEmailFormAction('<?= $actions[$i][0] ?>');"><?= $actions[$i][1] ?></a>
                    <?php } ?>
                </div>
            </div>

            <div>
            </div>
        </div>
        <div class="col-sm-2 col-xs-2" style="width: 12.3%;"></div>
        <div class="col-sm-5 col-xs-5" style="width: 9.9%;">
            <?= Html::button('Отменить', ['class' => 'btn darkblue text-white pull-right widthe-100 side-panel-close-button',]); ?>
        </div>
    </div>
</div>


<?php $form->end(); ?>


<script>
    sendEmailFormReady();
    <?php if($action === EmailController::ACTION_SEND || $action === EmailController::ACTION_CC) { ?>
    prepareToField();
    <?php } ?>
    <?php if($action === EmailController::ACTION_SEND) { ?>
    prepareUploader();
    <?php } ?>
</script>
