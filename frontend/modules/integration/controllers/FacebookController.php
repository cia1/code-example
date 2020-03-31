<?php

namespace frontend\modules\integration\controllers;

use common\models\employee\Employee;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;
use frontend\components\FrontendController;
use frontend\modules\integration\helpers\FacebokHelper;
use frontend\modules\integration\models\FacebookDataProvider;
use frontend\modules\integration\models\DateFromToFilterForm;
use Yii;
use yii\web\Cookie;
use yii\helpers\Url;

/**
 * Интеграция Facebook
 */
class FacebookController extends FrontendController
{

    private $integrationFBUser;
    private $integrationFBToken;

    public function actionIndex()
    {
        $uid = Yii::$app->request->get('uid');
        $token = Yii::$app->request->get('token');
        $auth = false;
        /** @var Employee $employee */
        $employee = Yii::$app->user->identity;
        if ($uid && $token) {
            $this->integrationFBUser = $uid;
            $this->integrationFBToken = $token;
            Yii::$app->response->cookies->add(new Cookie(['name' => 'integrationFBUser-' . $employee->company_id, 'value' => $uid]));
            Yii::$app->response->cookies->add(new Cookie(['name' => 'integrationFBToken-' . $employee->company_id, 'value' => $token]));
            $auth = true;
        } else {
            $this->integrationFBUser = Yii::$app->request->cookies->get('integrationFBUser-' . $employee->company_id);
            $this->integrationFBToken = Yii::$app->request->cookies->get('integrationFBToken-' . $employee->company_id);
            if ($this->integrationFBUser && $this->integrationFBToken) {
                $auth = true;
            }
        }
        if ($auth === false) {
            return $this->actionConnect();
        } else {
            return $this->actionTable();
        }
    }

    /**
     * Форма подключения аккаунта facebook
     */
    public function actionConnect()
    {
        return $this->render('connect', [
            'fbApplicationId' => FacebokHelper::FACEBOOK_APP_ID,
            'back' => isset($_GET['back']),
        ]);
    }


    /**
     * Таблица статистических данных
     *
     * @return string|null
     */
    public function actionTable()
    {
        $model = new DateFromToFilterForm();
        $model->load(Yii::$app->request->post());
        if ($model->validate() === false) {
            $model->resetDate();
        }
        try {
            $helper = new FacebokHelper($this->integrationFBUser, $this->integrationFBToken);
            $provider = new FacebookDataProvider($helper, $model->dateFrom, $model->dateTo);
            return $this->render('table', [
                'title' => $helper->graphUser()['name'],
                'model' => $model,
                'provider' => $provider,
                'total' => $provider->calculateTotal(['cpp', 'cpm', 'clicks', 'spend', 'reach', 'impressions']),
            ]);
        } catch (FacebookResponseException $e) {
            /** @var Employee $employee */
            $employee = Yii::$app->user->identity;
            Yii::$app->response->cookies->remove('integrationFBUser-' . $employee->company_id);
            Yii::$app->response->cookies->remove('integrationFBToken-' . $employee->company_id);
            if (strpos($e->getMessage(), 'Requires ads_management permission') > 0) {
                $message = 'Требуется разрешение "управление рекламными объявлениями". Если вы не знаете как это включить, удалите приложение <b>КУБ: Интеграция Ads</b> на странице <a href="https://www.facebook.com/settings?tab=business_tools" target="_blank" style="text-decoration:underline;">Бизнес-интеграции</a> и повторите попытку.';
            } else {
                $message = $e->getMessage();
            }
            Yii::$app->session->setFlash('error', $message);
            $this->redirect(Url::to('/integration/facebook?back'));
            return null;
        } catch (FacebookSDKException $e) {
            Yii::$app->session->setFlash('error', $e->getMessage());
            $this->redirect(Url::to('/integration/facebook?back'));
            return null;
        }
    }

    public function actionDisconnect()
    {
        /** @var Employee $employee */
        $employee = Yii::$app->user->identity;
        Yii::$app->response->cookies->remove('integrationFBUser-' . $employee->company_id);
        Yii::$app->response->cookies->remove('integrationFBToken-' . $employee->company_id);
        Yii::$app->session->set('popupMessage',
            'Интеграция отключена. Если хотите отозвать предоставленный доступ к Facebook, зайдите на страницу <a href="https://www.facebook.com/settings?tab=business_tools" target="_blank">Бизнес-интеграции</a> и отключите "КУБ: Интеграция Ads"');
        $this->redirect('/integration');
    }
}