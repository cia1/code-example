<?php

namespace frontend\modules\integration\controllers;

use common\models\employee\Employee;
use frontend\components\FrontendController;
use frontend\modules\integration\helpers\EvotorHelper;
use frontend\modules\integration\models\evotor\Device;
use frontend\modules\integration\models\evotor\Employee as EmployeeEvotor;
use frontend\modules\integration\models\evotor\Product;
use frontend\modules\integration\models\evotor\Receipt;
use frontend\modules\integration\models\evotor\ReceiptItem;
use frontend\modules\integration\models\evotor\Store;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\web\Response;

/**
 * Интеграция Эвотор (evotor.ru)
 *
 * @see /doc/integrations.md
 */
class EvotorController extends FrontendController
{

    public $enableCsrfValidation = false; //В контроллер будут приходить данные из Эвотора

    /**
     * Настройки интегорации
     * Настройки -> Интеграция -> Эвотор -> Настройки
     */
    public function actionSetting()
    {
        if (Yii::$app->request->get('layout') === 'no') {
            $this->layout = false;
        }
        /** @var Employee $employee */
        $employee = Yii::$app->user->identity;
        return $this->render('setting', [
            'count' => count($employee->companies),
            'company' => $employee->company->getShortName(),
        ]);
    }

    /**
     * Отключение интеграции
     * Настройки -> Интеграция -> Эвотор -> Отключить
     */
    public function actionDisconnect()
    {
        /** @var Employee $employee */
        $employee = Yii::$app->user->identity;
        EvotorHelper::disconnect($employee);
        Yii::$app->session->setFlash('success', 'Интеграция Эвотор отключена');
        $this->redirect('/integration');
    }

    /**
     * Обработка веб-хука
     * Ответ должен быть в формате JSON, для веб-хуков типа "уведомление" ответ игнорируется
     *
     * @return string
     */
    public function actionHook()
    {
        $request = json_decode(file_get_contents('php://input'), true) ?? null;
        if (isset($request[0]) === false) {
            $request = [$request];
        }
        $action = Yii::$app->request->get('action');
        $result = null;
        foreach ($request as $item) {
            $result = EvotorHelper::hook($action, $item, Yii::$app->request->get('path'), Yii::$app->request->headers->get('Authorization'));
        }
        Yii::$app->response->format = Response::FORMAT_JSON;
        return $result;
    }

    /**
     * Список сущностей Эвотор
     *
     * @return string
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * Таблица магазинов
     *
     * @return string
     */
    public function actionStore()
    {
        return $this->_render(new Store(), 'магазины');
    }

    /**
     * Таблица терминалов
     *
     * @return string
     */
    public function actionDevice()
    {
        return $this->_render(new Device(), 'терминалы');
    }

    /**
     * Таблица сотрудников
     *
     * @return string
     */
    public function actionEmployee()
    {
        return $this->_render(new EmployeeEvotor(), 'сотрудники');
    }

    /**
     * Таблица чеков
     *
     * @return string
     */
    public function actionReceipt()
    {
        return $this->_render(new Receipt(), 'чеки');
    }

    /**
     * Таблица позиций чека
     *
     * @param int $id ID чека
     * @return string
     */
    public function actionReceiptItem(int $id)
    {
        return $this->_render(new ReceiptItem(), 'позиции чека', ReceiptItem::find()->where(['receipt_id' => $id]));
    }

    /**
     * Таблица товаров
     *
     * @return string
     */
    public function actionProduct()
    {
        /** @var Employee $employee */
        $employee = Yii::$app->user->identity;
        return $this->_render(new Product(), 'товары', Product::findProduct()->andWhere(['company_id' => $employee->company_id]));
    }

    private function _render(ActiveRecord $model, string $entityName, ActiveQuery $query = null)
    {
        /** @var Employee $employee */
        $employee = Yii::$app->user->identity;
        if ($query === null) {
            $query = $model::find()->where(['company_id' => $employee->company_id]);
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
