<?php

namespace frontend\modules\integration\controllers;

use common\models\employee\Employee;
use frontend\components\FrontendController;
use frontend\modules\integration\helpers\ImapHelper;
use frontend\modules\integration\models\EmailLetterForm;
use frontend\modules\integration\models\EmailSettingsForm;
use frontend\modules\integration\models\ImapDataProvider;
use Yii;
use yii\base\InvalidConfigException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\widgets\ActiveForm;
use yii\helpers\FileHelper;
use yii\base\Exception as BaseException;
use unyii2\imap\Exception;

/**
 * Интеграция электронной почты: просмотр входящей почты, отправка писем
 */
class EmailController extends FrontendController
{

    const MESSAGE_IN_TABLE_LENGTH = 300; //Количество символов начала текста письма, которое будет выводиться в таблице

    const ACTION_VIEW = 'view'; //просмотр письма
    const ACTION_REPLY = 'reply'; //ответ
    const ACTION_CC = 'cc'; //пересылка
    const ACTION_SEND = 'send'; //новое письмо

    /**
     * Настройки интегорации
     * Настройки -> Интеграция -> Почта -> Настройки
     */
    public function actionSetting()
    {
        /** @var Employee $employee */
        $employee = Yii::$app->user->identity;
        $model = new EmailSettingsForm();
        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->request->post());
            if (Yii::$app->request->isAjax && !Yii::$app->request->isPjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ActiveForm::validate($model);
            }
            if ($model->storeToUser($employee)) {
                Yii::$app->session->setFlash('success', 'Настройки интеграции сохранены');
                return $this->redirect('setting');
            }
        } else {
            $integration = $employee->integration(Employee::INTEGRATION_EMAIL);
            if ($integration !== null) { //интеграция настроена - передать в форму параметры
                /** @var array $integration */
                $model->load($integration, '');
            } else { //интеграция не настроена - определить сервер на основе e-mail'а
                $model->setEmail($employee->email);
            }
        }
        return $this->render('setting', [
            'model' => $model,
        ]);
    }

    /**
     * Отключение интеграции
     * Настройки -> Интеграция -> Почта -> Отключить
     */
    public function actionDisconnect()
    {
        /** @var Employee $employee */
        $employee = Yii::$app->user->identity;
        $employee->saveIntegration(Employee::INTEGRATION_EMAIL, null);
        Yii::$app->session->setFlash('success', 'Интеграция электронной почты отключена');
        $this->redirect('/integration');
    }

    /**
     * Список входящих писем
     *
     * @return string
     * @throws BaseException
     * @throws InvalidConfigException
     */
    public function actionIndex()
    {
        $dataProvider = new ImapDataProvider(self::_getHelper());
        $withoutDetail = [];
        foreach ($dataProvider->getModels() as $item) {
            if (isset($item['detail']) === false) {
                $withoutDetail[] = $item['id'];
            }
        }
        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'withoutDetail' => $withoutDetail,
        ]);
    }

    /**
     * В фоне подгружает подробную информацию письма
     *
     * @param int $id
     * @return array
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotFoundHttpException
     * @throws BaseException
     */
    public function actionDetail(int $id)
    {
        $data = self::_getHelper()->getMailCache($id);
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (mb_strlen($data['textPlain']) > self::MESSAGE_IN_TABLE_LENGTH) {
            $data['textPlain'] = mb_substr($data['textPlain'], 0, EmailController::MESSAGE_IN_TABLE_LENGTH) . '...';
        }
        return [
            'textPlain' => $data['textPlain'],
            'attachments' => $data['attachments'],
        ];
    }

    /**
     * Просмотр письма (всплывающее окно)
     *
     * @param int $id
     * @return string
     * @throws BaseException
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotFoundHttpException
     */
    public function actionView(int $id)
    {
        $data = self::_getHelper()->getMailCache($id);
        $form = new EmailLetterForm($data);
        return $this->renderAjax('item', [
            'uid' => $id,
            'letterForm' => $form,
            'date' => $data['date'],
            'action' => self::ACTION_VIEW,
            'actions' => [
                ['reply', 'Ответить'],
                ['cc', 'Переслать'],
            ],
            'readonly' => ['from', 'to', 'subject', 'body', 'attachments'],
        ]);
    }

    /**
     * Форма ответа на письмо
     *
     * @param int $id
     * @return string
     * @throws BaseException
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotFoundHttpException
     */
    public function actionReply(int $id)
    {
        $data = self::_getHelper()->getMailCache($id);
        $form = new EmailLetterForm($data, false);
        if ($form->replyTo) {
            $form->from = $form->to;
            $form->to = $form->replyTo;
        } else {
            list($form->from, $form->to) = [$form->to, $form->from];
        }
        unset($form->replyTo);
        $form->subject = 'Re: ' . $form->subject;
        $form->body = "> " . str_replace(["r", "\n"], ['', "> \n"], $form->body);
        return $this->renderAjax('item', [
            'uid' => $id,
            'letterForm' => $form,
            'date' => $data['date'],
            'action' => self::ACTION_REPLY,
            'actions' => [
                ['reply', 'Ответить'],
                ['cc', 'Переслать'],
            ],
            'readonly' => ['from', 'to'],
        ]);
    }

    /**
     * Форма пересылки письма
     *
     * @param int $id
     * @return string
     * @throws BaseException
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotFoundHttpException
     */
    public function actionCc(int $id)
    {
        $data = self::_getHelper()->getMailCache($id);
        $form = new EmailLetterForm($data);
        $form->to = null;
        $form->subject = 'Fwd: ' . $form->subject;
        return $this->renderAjax('item', [
            'uid' => $id,
            'letterForm' => $form,
            'date' => $data['date'],
            'action' => self::ACTION_CC,
            'actions' => [
                ['cc', 'Переслать'],
                ['reply', 'Ответить'],
            ],
            'readonly' => ['from', 'subject', 'body', 'attachments'],
        ]);
    }

    /**
     * Форма отправки нового письма
     *
     * @return string
     */
    public function actionSend()
    {
        /** @var Employee $employee */
        $employee = Yii::$app->user->identity;
        $form = new EmailLetterForm();
        $form->from = $employee->integration(Employee::INTEGRATION_EMAIL)['smtpEmail'];
        return $this->renderAjax('item', [
            'uid' => null,
            'letterForm' => $form,
            'date' => null,
            'action' => self::ACTION_SEND,
            'actions' => [
                ['send', 'Отправить'],
            ],
            'readonly' => ['from'],
        ]);
    }

    /**
     * Скачивание вложения
     *
     * @param int    $id       UID письма
     * @param string $attachId Идентификатор вложения
     * @param string $file     Короткое ммя файла вложения
     * @throws InvalidConfigException
     * @throws NotFoundHttpException
     */
    public function actionAttachment(int $id, string $attachId, string $file)
    {
        $fileName = ImapHelper::fullAttachmentFileName(Yii::$app->user->getId(), $id, $attachId, $file);
        if ($fileName === null) {
            throw new NotFoundHttpException('Вложение не существует');
        }
        header('Content-Type: ' . FileHelper::getMimeType($fileName));
        header('Content-Disposition: attachment; filename="' . $file . '"');
        readfile($fileName);
    }

    /**
     * POST-обработчик отправки письма
     *
     * @return array|Response
     * @throws BaseException
     * @throws Exception
     * @throws ForbiddenHttpException
     * @throws InvalidConfigException
     * @throws NotFoundHttpException
     */
    public function actionPost()
    {
        $post = Yii::$app->request->post();
        $form = new EmailLetterForm();
        $form->load($post);
        if (Yii::$app->request->isAjax && !Yii::$app->request->isPjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ActiveForm::validate($form);
        }
        if ($post['action'] === self::ACTION_CC) {
            $source = $this->_getHelper()->getMailCache($post['uid']);
            $form->body = $source['textHtml'] ?? $source['textPlain'];
            $form->attachments = $source['attachments'];
        }

        if ($form->send() === true) {
            Yii::$app->session->setFlash('success', 'Письмо отправлено');
            return $this->redirect('/integration/email');
        }
        throw new ForbiddenHttpException(implode("\n", $form->getErrorSummary(false)));
    }

    /**
     * @throws BaseException
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function actionManyDelete()
    {
        $this->_getHelper()->delete(array_keys(Yii::$app->request->post('Imap')));
        Yii::$app->response->statusCode = 302;
    }

    /**
     * Возвращает хелпер и выполняет редирект на страницу настроек, если интеграция не настроена
     *
     * @return ImapHelper
     * @throws InvalidConfigException
     * @throws BaseException
     */
    private function _getHelper(): ImapHelper
    {
        /** @var Employee $employee */
        $employee = Yii::$app->user->identity;
        /** @var array|null $integrationEmail */
        $integrationEmail = $employee->integration(Employee::INTEGRATION_EMAIL);
        if ($integrationEmail === null) {
            $this->redirect('/integration/default/email');
        }
        return new ImapHelper($integrationEmail, $employee->company_id);
    }

}