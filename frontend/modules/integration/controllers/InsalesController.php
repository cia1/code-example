<?php

namespace frontend\modules\integration\controllers;

use common\models\employee\Employee;
use common\models\insales\Account;
use frontend\components\FrontendController;
use frontend\modules\integration\helpers\InsalesHelper;
use frontend\modules\integration\models\insales\Client;
use frontend\modules\integration\models\insales\Order;
use frontend\modules\integration\models\insales\OrderPosition;
use frontend\modules\integration\models\insales\Product;
use frontend\modules\integration\models\insales\ShippingAddress;
use Yii;
use yii\base\InvalidConfigException;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * Интеграция In Sales CMS
 *
 * @see /doc/integrations.md
 */
class InsalesController extends FrontendController
{

    public $enableCsrfValidation = false; //В контроллер будут приходить данные из InSales CMS

    /**
     * Настройки интегорации
     * Настройки -> Интеграция -> In Sales CMS -> Настройки
     */
    public function actionSetting()
    {
        /** @var Employee $employee */
        $employee = Yii::$app->user->identity;
        return $this->render('setting', [
            'count' => count($employee->companies),
            'company' => $employee->company->getShortName(),
        ]);
    }

    /**
     * Веб-хук установки приложения на удалённом сервере
     * Это странно, но InSales не имеет никаких средств авторизации запроса
     */
    public function actionInstall()
    {
        $request = Yii::$app->request;
        InsalesHelper::install($request->get('insales_id'), $request->get('shop'), $request->get('token'));
    }

    /**
     * Удалённая авторизация пользователя
     * Дополняет отсутствующие данные в {{insales_account}} и устанавливает веб-хуки
     *
     * @throws InvalidConfigException
     */
    public function actionLogin()
    {
        $request = Yii::$app->request;
        $account = Account::findOne(['id' => $request->get('insales_id')]);
        if ($account === null) {
            Yii::$app->session->setFlash('error', 'Ошибка авторизации: приложение не установлено');
            return $this->render('setting');
        }
        /** @var Employee $employee */
        $employee = Yii::$app->user->identity;
        if ($account->user_id === null) {
            $account->company_id = $employee->company_id;
            $account->user_id = $request->get('user_id');
            $account->shop = $request->get('shop');
            if ($account->save() === false) {
                return $this->render('setting');
            }
            InsalesHelper::setWebHook($account);
        }
        $employee->saveIntegration('insales', true);
        $this->redirect('/integration/insales');
        return null;
    }

    public function actionDestroy()
    {
        Account::deleteAll(['id' => Yii::$app->request->get('insales_id')]);
    }

    /**
     * Отключение интеграции
     * Настройки -> Интеграция -> InSales CMS -> Отключить
     */
    public function actionDisconnect()
    {
        /** @var Employee $employee */
        $employee = Yii::$app->user->identity;
        InsalesHelper::disconnect($employee);
        Yii::$app->session->setFlash('success', 'Интеграция InSales CMS отключена');
        $this->redirect('/integration');
    }

    /**
     * Обработка веб-хука
     */
    public function actionHook()
    {
        $request = json_decode(file_get_contents('php://input'), true) ?? null;
        InsalesHelper::hook($request);
    }

    /**
     * Список таблиц
     *
     * @return string
     */
    public function actionIndex()
    {
        return $this->render('index');
    }


    /**
     * Таблица "Клиенты"
     *
     * @return string
     */
    public function actionClient(): string
    {
        return $this->_render(new Client(), 'клиенты');
    }

    /**
     * Таблица "Товары"
     *
     * @return string
     */
    public function actionProduct(): string
    {
        return $this->_render(new Product(), 'товары');
    }

    /**
     * Таблица "Заказы"
     *
     * @param int|null $client Идентификатор клиента
     * @return string
     */
    public function actionOrder(int $client = null): string
    {
        $condition = ['archived' => false];
        if ($client) {
            $condition['client_id'] = $client;
        }
        return $this->_render(new Order(), 'заказы', Order::find()->where($condition));
    }

    /**
     * Таблица "адреса доставки"
     *
     * @param int $clientId Идентификатор клиента
     * @return string
     */
    public function actionShippingAddress(int $clientId): string
    {
        return $this->_render(new ShippingAddress(), 'адреса доставки', ShippingAddress::find()->where(['client_id' => $clientId]));
    }

    /**
     * Таблица "позиции заказа"
     *
     * @param int $orderId Идентификатор заказа
     * @return string
     */
    public function actionPosition(int $orderId): string
    {
        return $this->_render(new OrderPosition(), 'позиции заказа', OrderPosition::find()->where(['order_id' => $orderId]));
    }


    private function _render(ActiveRecord $model, string $entityName, ActiveQuery $query = null)
    {
        if ($query === null) {
            $query = $model::find();
        }
        return $this->render('item', [
            'entityName' => $entityName,
            'model' => $model,
            'dataProvider' => new ActiveDataProvider([
                'query' => $query,
            ]),
        ]);
    }

}