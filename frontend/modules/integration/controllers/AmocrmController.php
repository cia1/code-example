<?php

namespace frontend\modules\integration\controllers;

use common\models\employee\Employee;
use frontend\modules\integration\models\amocrm\Contact;
use frontend\modules\integration\models\amocrm\Customer;
use frontend\modules\integration\models\amocrm\Lead;
use frontend\modules\integration\models\amocrm\Note;
use frontend\modules\integration\models\amocrm\Task;
use frontend\components\FrontendController;
use frontend\modules\integration\helpers\AMOcrmHelper;
use frontend\modules\integration\models\AmocrmSettingsForm;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\web\Response;
use yii\widgets\ActiveForm;

/**
 * Интеграция AMOcrm
 */
class AmocrmController extends FrontendController
{

    public $enableCsrfValidation = false; //В контроллер будут приходить данные из AMOcrm

    /**
     * Настройки интеграции
     * Настройки -> Интеграция -> AMOCrm -> Настройки
     */
    public function actionSetting()
    {
        /** @var Employee $employee */
        $employee = Yii::$app->user->identity;
        $model = new AmocrmSettingsForm();
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
            $model->load($employee->integration(Employee::INTEGRATION_AMOCRM), '');
        }
        return $this->render('setting', [
            'model' => $model,
        ]);
    }

    /**
     * Отключение интеграции
     * Настройки -> Интеграция -> AMOCrm -> Отключить
     */
    public function actionDisconnect()
    {
        /** @var Employee $employee */
        $employee = Yii::$app->user->identity;
        AMOcrmHelper::disconnect($employee);
        Yii::$app->session->setFlash('success', 'Интеграция AMOcrm отключена');
        $this->redirect('/integration');
    }

    /**
     * Обработка веб-хука
     *
     * @return string
     */
    public function actionHook()
    {
        $data = Yii::$app->request->post();
        foreach ($data as $entity => $item) {
            foreach ($item as $action => $subitem) {
                AMOcrmHelper::hook($entity, $action, $subitem, $data['account']['id']);
            }
        }
        return 'OK';
    }

    /**
     * Список сущностей AMOcrm
     *
     * @return string
     */
    public function actionIndex()
    {
        return $this->render('index', []);
    }

    /**
     * Таблица сделок
     *
     * @return string
     */
    public function actionLead()
    {
        return $this->_render(new Lead(), 'сделки');
    }

    /**
     * Таблица контактов
     *
     * @return string
     */
    public function actionContact()
    {
        return $this->_render(new Contact(), 'контакты', Contact::find()->where(['type' => Contact::TYPE_CONTACT]));
    }

    /**
     * Таблица компаний
     *
     * @return string
     */
    public function actionCompany()
    {
        /** @var Employee $employee */
        $employee = Yii::$app->user->identity;
        return $this->_render(new Contact(), 'компании', Contact::find()->where(['company_id' => $employee->company_id, 'type' => Contact::TYPE_COMPANY]));
    }

    /**
     * Таблица покупателей
     *
     * @return string
     */
    public function actionCustomer()
    {
        return $this->_render(new Customer(), 'покупатели');
    }

    /**
     * Таблица задач
     *
     * @return string
     */
    public function actionTask()
    {
        return $this->_render(new Task(), 'задачи');
    }

    /**
     * Таблица примечаний
     *
     * @param int $entity Тип сущности @see Note::TYPE
     * @param int $id     ID сущности
     * @return string
     */
    public function actionNote(int $entity, int $id)
    {
        return $this->_render(new Note(), 'примечения', Note::find()->where([
            'element_type' => (string)$entity,
            'element_id' => $id,
        ]));
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