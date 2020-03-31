<?php

namespace frontend\modules\integration\controllers;

use common\models\bitrix24\User;
use common\models\employee\Employee;
use frontend\components\FrontendController;
use frontend\modules\integration\helpers\Bitrix24Helper;
use frontend\modules\integration\models\bitrix24\Catalog;
use frontend\modules\integration\models\bitrix24\Company;
use frontend\modules\integration\models\bitrix24\Contact;
use frontend\modules\integration\models\bitrix24\Deal;
use frontend\modules\integration\models\bitrix24\DealPosition;
use frontend\modules\integration\models\bitrix24\Invoice;
use frontend\modules\integration\models\bitrix24\Product;
use frontend\modules\integration\models\bitrix24\Section;
use frontend\modules\integration\models\Bitrix24SettingsForm;
use Yii;
use yii\base\InvalidConfigException;
use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\httpclient\Exception;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\widgets\ActiveForm;

/**
 * Интеграция Битрикс24
 *
 * `/integration/bitrix24/setting` - настройки подключения
 * `/integration/bitrix24/disconnect` - отключение интеграции
 * `/integration/bitrix24/token` - запрос токена (возврат)
 * `/integration/bitrix24` - список сущностей
 * `/integration/bitrix24/catalog` - список торговых каталогов
 * `/integration/bitrix24/<catalogId>` - категории торгового каталога
 * `/integration/bitrix24/<catalogId>/<sectionId>` - список товаров
 * `/integration/bitrix24/company` - список компаний
 * `/integration/bitrix24/contact` - список контактов
 * `/integration/bitrix24/deal` - список сделок
 * `/integration/bitrix24/deal/<id>` - список позиций сделки
 * `/integration/bitrix24/deal/<id>/invoice` - список счетов по сделке
 * `/integration/bitrix24/invoice` - список счетов
 */
class Bitrix24Controller extends FrontendController
{

    public $enableCsrfValidation = false; //В контроллер будут приходить данные из Битрикс24

    /**
     * Настройки интеграции
     * Настройки -> Интеграция -> Битрикс 24 -> Настройки
     */
    public function actionSetting()
    {
        /** @var Employee $employee */
        $employee = Yii::$app->user->identity;
        $model = new Bitrix24SettingsForm();
        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->request->post());
            if (Yii::$app->request->isAjax && !Yii::$app->request->isPjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ActiveForm::validate($model);
            }
            if ($model->validate()) {
                return Bitrix24Helper::redirectToken($model->host);
            }
        } else {
            $model->load($employee->integration(Employee::INTEGRATION_BITRIX24), '');
        }
        return $this->render('setting', [
            'model' => $model,
        ]);
    }

    /**
     * Запрос токена (возврат)
     *
     * @throws InvalidConfigException
     * @throws Exception
     */
    public function actionToken()
    {
        $helper = new Bitrix24Helper();
        $code = Yii::$app->request->get('code');
        if ($code === null) {
            $this->layout = false;
            return $this->render('install');
        }
        $result = $helper->post(Bitrix24Helper::getTokenURL(Yii::$app->request->get('code')), []);
        if ($result === null || is_array($result) === false || isset($result['member_id']) === false || isset($result['access_token']) === false || isset($result['refresh_token']) === false) {
            Yii::$app->session->setFlash('error', 'Авторизация не удалась');
            return $this->redirect('/integration/bitrix24/setting');
        }
        /** @var Employee $employee */
        $employee = Yii::$app->user->identity;
        $employee->setIntegration(Employee::INTEGRATION_BITRIX24, [
            'host' => parse_url($result['client_endpoint'])['host'],
            'token' => $result['access_token'],
            'refresh' => $result['refresh_token'],
        ]);
        $user = User::createOrLoad($employee->company_id, $result['member_id']);
        $user->save();

        $helper = Bitrix24Helper::instance($employee);
        if ($helper->setHooks() === false) {
            Yii::$app->session->setFlash('error', 'Не удалось настроить веб-хуки. Обратитесь к администрации для решения проблемы');
            $this->redirect('/integration/bitrix24/setting');
        }
        $employee->saveIntegration();
        return $this->redirect('/integration/bitrix24');
    }

    /**
     * Отключение интеграции
     * Настройки -> Интеграция -> Битрикс 24 -> Отключить
     */
    public function actionDisconnect()
    {
        /** @var Employee $employee */
        $employee = Yii::$app->user->identity;
        Bitrix24Helper::disconnect($employee);
        Yii::$app->session->setFlash('success', 'Интеграция Битрикс24 отключена');
        $this->redirect('/integration');
    }

    /**
     * Обработчик веб-хуков
     *
     * @throws InvalidConfigException
     */
    public function actionHook()
    {
        $post = Yii::$app->request->post();
        if (isset($post['auth']) === false || isset($post['event']) === false || isset($post['data']) === false) {
            return;
        }
        Bitrix24Helper::hook(
            $post['auth']['member_id'],
            $post['event'],
            $post['data']['FIELDS']['ID'],
            new Bitrix24Helper($post['auth']['domain'], $post['auth']['access_token'])
        );
    }

    /**
     * Список сущностей Битрикс24
     *
     * @return string
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * Таблица торговых каталогов
     *
     * @return string
     */
    public function actionCatalog()
    {
        return $this->_render(new Catalog(), 'торговые каталоги', Catalog::find()->where(self::_companyIdCondition()));
    }

    /**
     * Таблица категорий торгового каталога
     *
     * @param int $catalogId
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionSection(int $catalogId)
    {
        /** @var Employee $employee */
        $employee = Yii::$app->user->identity;
        $catalog = Catalog::findOne(['company_id' => $employee->company_id, 'id' => $catalogId]);
        if ($catalog === null) {
            throw new NotFoundHttpException('Торгового каталога не существует');
        }
        $flat = Section::flatWithLevel($employee->company_id, $catalogId);
        return $this->render('item', [
            'entityName' => 'каталог &laquo;' . $catalog->name . '&raquo;',
            'model' => new Section(),
            'dataProvider' => new ArrayDataProvider([
                'models' => $flat,
            ]),
        ]);
    }

    /**
     * Таблица со списком товаров
     *
     * @param int $catalogId Идентификатор торгового каталога
     * @param int $sectionId Идентификатор группы товаров
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionProduct(int $catalogId, int $sectionId)
    {
        $catalog = Catalog::findOne(self::_companyIdCondition(['id' => $catalogId]));
        if ($catalog === null) {
            throw new NotFoundHttpException('Торгового каталога не существует');
        }

        $section = Section::findOne(self::_companyIdCondition(['id' => $sectionId]));
        if ($section === null) {
            throw new NotFoundHttpException('Группы товаров не существует');
        }
        return $this->_render(
            new Product(),
            'товары (' . $catalog->name . ' / ' . $section->name . ')',
            Product::find()->where(self::_companyIdCondition(['section_id' => $sectionId]))->orderBy('sort')
        );
    }

    public function actionCompany()
    {
        return $this->_render(new Company(), 'компании', Company::find()->where(self::_companyIdCondition()));
    }

    public function actionContact()
    {
        return $this->_render(new Contact(), 'контакты', Contact::find()->where(self::_companyIdCondition()));
    }

    public function actionDeal()
    {
        return $this->_render(new Deal(), 'сделки', Deal::find()->where(self::_companyIdCondition()));
    }

    /**
     * Список позиций сделки
     *
     * @param int $id Идентификатор сделки
     * @return string
     */
    public function actionDealPosition(int $id)
    {
        return $this->_render(new DealPosition(), 'позиции сделки', DealPosition::find()->where(self::_companyIdCondition(['deal_id' => $id])));
    }

    public function actionInvoice()
    {
        return $this->_render(new Invoice(), 'счета', Invoice::find()->where(self::_companyIdCondition()));
    }

    public function actionDealInvoice(int $id)
    {
        /** @var Employee $employee */
        $employee=Yii::$app->user->identity;
        $deal = Deal::findOne(['company_id' => $employee->company_id, 'id' => $id]);
        return $this->_render(new Invoice(), 'счета сделки &laquo;' . $deal->title . '&raquo;', Invoice::find()->where(['company_id' => $employee->company_id, 'deal_id' => $id]));
    }

    public function actionCompanyInvoice(int $id)
    {
        /** @var Employee $employee */
        $employee=Yii::$app->user->identity;
        $company = Deal::findOne(['company_id' => $employee->company_id, 'id' => $id]);
        return $this->_render(new Invoice(), 'счета компании &laquo;' . $company->title . '&raquo;', Invoice::find()->where(['company_id' => $employee->company_id, 'bitrix24_company_id' => $id]));
    }

    public function actionContactInvoice(int $id)
    {
        /** @var Employee $employee */
        $employee=Yii::$app->user->identity;
        $contact = Deal::findOne(['company_id' => $employee->company_id, 'id' => $id]);
        return $this->_render(new Invoice(), 'счета контакта &laquo;' . $contact->fullContact . '&raquo;', Invoice::find()->where(['company_id' => $employee->company_id, 'contact_id' => $id]));
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

    private static function _companyIdCondition(array $condition = []): array
    {
        /** @var Employee $employee */
        $employee = Yii::$app->user->identity;
        $condition['company_id'] = $employee->company_id;
        return $condition;
    }
}