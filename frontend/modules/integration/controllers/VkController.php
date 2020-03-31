<?php

namespace frontend\modules\integration\controllers;

use common\models\employee\Employee;
use frontend\components\FrontendController;
use frontend\modules\integration\helpers\VkHelper;
use frontend\modules\integration\models\DateFromToFilterForm;
use frontend\modules\integration\models\VkDataProvider;
use frontend\modules\integration\models\VkSettingsForm;
use Yii;
use yii\web\Cookie;
use yii\helpers\Url;
use yii\web\ForbiddenHttpException;
use yii\web\Response;
use yii\widgets\ActiveForm;

/**
 * Интеграция Вконтакте
 */
class VkController extends FrontendController
{

    /**
     * Настройки интеграции
     * Настройки -> Интеграция -> ВКонтакте -> Настройки
     */
    public function actionSetting()
    {
        /** @var Employee $user */
        $user = Yii::$app->user->identity;
        $model = new VkSettingsForm();
        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->request->post());
            if (Yii::$app->request->isAjax && !Yii::$app->request->isPjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ActiveForm::validate($model);
            }
            if ($model->storeToUser($user)) {
                Yii::$app->session->setFlash('success', 'Настройки интеграции сохранены');
                return $this->redirect('/integration/vk');
            }
        } else {
            $model->load($user->integration(Employee::INTEGRATION_VK), '');
        }
        return $this->render('setting', [
            'model' => $model,
        ]);
    }

    /**
     * Отключение интеграции
     * Настройки -> Интеграция -> ВКонтакте -> Отключить
     */
    public function actionDisconnect()
    {
        /** @var Employee $employee */
        $employee = Yii::$app->user->identity;
        $employee->saveIntegration(Employee::INTEGRATION_VK, null);
        Yii::$app->session->setFlash('success', 'Интеграция ВКонтакте отключена');
        $this->redirect('/integration');
    }

    private $_token;
    /** @var DateFromToFilterForm */
    private $_filterModel;

    public function __construct($id, $module, $config = [])
    {
        $token = Yii::$app->request->get('token');
        /** @var Employee $employee */
        $employee = Yii::$app->user->identity;
        if ($token !== null) {
            Yii::$app->response->cookies->add(new Cookie(['name' => 'vkToken-' . $employee->company_id, 'value' => $token]));
        } else {
            $token = Yii::$app->request->cookies->get('vkToken-' . $employee->company_id)->value ?? null;
        }
        $this->_token = $token;
        $model = new DateFromToFilterForm();
        $model->load(Yii::$app->request->post());
        if ($model->validate() === false) {
            $model->resetDate();
        }
        $this->_filterModel = $model;
        parent::__construct($id, $module, $config);
    }

    public function actionIndex()
    {
        if ($this->_token !== null) {
            return $this->actionTableIndex();
        }
        $code = Yii::$app->request->get('code');
        if ($code !== null) {
            return $this->_token($code);
        }
        return $this->_auth();
    }

    public function actionTableIndex()
    {
        $helper = $this->_getHelper();
        $provider = new VkDataProvider($helper->groupByCompany());
        return $this->render('table', [
            'model' => $this->_filterModel,
            'title' => 'Статистика по рекламным компаниям',
            'provider' => $provider,
            'total' => $provider->calculateTotal(['cpc', 'spent', 'impressions', 'clicks', 'reach']),
        ]);
    }

    /**
     * Данные по всем компаниям с группировкой по дням
     *
     * @return string
     */
    public function actionByDays(): string
    {
        $helper = $this->_getHelper();
        $provider = new VkDataProvider($helper->groupByDays());
        return $this->render('table', [
            'backlink' => Url::to('/integration/vk'),
            'model' => $this->_filterModel,
            'title' => 'Статистика по дням',
            'provider' => $provider,
        ]);
    }

    /**
     * Данные по дням по отдельной реклмной компании
     *
     * @param int $id Идентификатор рекламной компании
     * @return string
     * @throws ForbiddenHttpException
     */
    public function actionByCompany(int $id)
    {
        $helper = $this->_getHelper();
        $helper->filterByCompany($id);
        $provider = new VkDataProvider($helper->groupByDays());
        $company = $helper->companyInfo($id);
        if ($company === null) {
            throw new ForbiddenHttpException('Такой рекламной компании нет');
        }
        return $this->render('table', [
            'backlink' => Url::to('/integration/vk'),
            'model' => $this->_filterModel,
            'title' => 'Статистика по &laquo;' . $company['name'] . '&raquo;',
            'provider' => $provider,
        ]);
    }

    /**
     * Авторизация, первый этап - запрос кода авторизации
     *
     * @return Response
     */
    private function _auth(): Response
    {
        return $this->redirect(VkHelper::getAuthLink());
    }

    /**
     * Авторизация, второй этап - запрос токена доступа
     *
     * @param string $code Код, полученный на первом этапе авторизации
     * @return Response
     */
    private function _token(string $code): Response
    {
        $error = null;
        $token = VkHelper::getToken($code, $error);
        if ($token === null) {
            if ($error === null) {
                $error = 'Авторизоваться не удалось';
            }
            Yii::$app->session->setFlash('error', $error);
            return $this->redirect('/integration/vk/setting');
        }
        return $this->redirect('/integration/vk?token=' . $token);
    }

    private function _getHelper()
    {
        /** @var Employee $employee */
        $employee = Yii::$app->user->identity;
        return new VkHelper(
            $this->_token,
            $employee->integration(Employee::INTEGRATION_VK)['accountId'],
            $this->_filterModel->dateFrom,
            $this->_filterModel->dateTo
        );
    }

}