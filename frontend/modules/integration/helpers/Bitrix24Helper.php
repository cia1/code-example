<?php

namespace frontend\modules\integration\helpers;

use common\models\bitrix24\Catalog;
use common\models\bitrix24\Company;
use common\models\bitrix24\Deal;
use common\models\bitrix24\DealPosition;
use common\models\bitrix24\Invoice;
use common\models\bitrix24\Product;
use common\models\bitrix24\Section;
use common\models\bitrix24\User;
use common\models\bitrix24\Vat;
use common\models\employee\Employee;
use frontend\modules\integration\models\amocrm\Contact;
use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\Url;
use yii\httpclient\Client;
use yii\httpclient\Exception;
use yii\httpclient\Request;
use yii\web\Response;

/**
 * Помощник интеграции Битрикс24
 *
 * @see docs/integrations.md
 */
class Bitrix24Helper
{

    const APPLICATION_NAME = 'КУБ-24';
    const APPLICATION_ID = 'app.5db5ac5b31d032.73869582'; //ID приложения
    const APPLICATION_SECRET = 'pC441h1w77GG2xPzem3P2F1roekY7X2GhD607Fks8jnrYF7DuK'; //секретный ключ приложения
    const OATH_TOKEN_URL = 'oauth.bitrix.info/oauth/token/';

    /**
     * События, которые могут быть обработаны.
     * self::EVENTS содержит массив событий, на которые будет подписано приложение при установке.
     *
     * @see https://dev.1c-bitrix.ru/rest_help/rest_sum/events/events.php
     */
    const EVENT_SECTION_CREATE = 'ONCRMPRODUCTSECTIONADD'; //группа товаров (создание)
    const EVENT_SECTION_UPDATE = 'ONCRMPRODUCTSECTIONUPDATE'; //группа товаров (изменение)
    const EVENT_SECTION_DELETE = 'ONCRMPRODUCTSECTIONDELETE'; //группа товаров (удаление)
    const EVENT_PRODUCT_CREATE = 'ONCRMPRODUCTADD'; //товар (добавление)
    const EVENT_PRODUCT_UPDATE = 'ONCRMPRODUCTUPDATE'; //товар (изменение)
    const EVENT_PRODUCT_DELETE = 'ONCRMPRODUCTDELETE'; //товар (удаление)
    const EVENT_COMPANY_CREATE = 'ONCRMCOMPANYADD'; //компания (создание)
    const EVENT_COMPANY_UPDATE = 'ONCRMCOMPANYUPDATE'; //компания (измененеие)
    const EVENT_COMPANY_DELETE = 'ONCRMCOMPANYDELETE'; //компания (удаление)
    const EVENT_CONTACT_CREATE = 'ONCRMCONTACTADD'; //контакт (добавление)
    const EVENT_CONTACT_UPDATE = 'ONCRMCONTACTUPDATE'; //контакт (изменение)
    const EVENT_CONTACT_DELETE = 'ONCRMCONTACTDELETE'; //контакт (удаление)
    const EVENT_DEAL_CREATE = 'ONCRMDEALADD'; //сделка (добавление)
    const EVENT_DEAL_UPDATE = 'ONCRMDEALUPDATE'; //сделка (изменение)
    const EVENT_DEAL_DELETE = 'ONCRMDEALDELETE'; //сделка (удаление)
    const EVENT_INVOICE_CREATE = 'ONCRMINVOICEADD'; //заявка (создание)
    const EVENT_INVOICE_STATUS = 'ONCRMINVOICESETSTATUS'; //заявка (обновление статуса)
    const EVENT_INVOICE_UPDATE = 'ONCRMINVOICEUPDATE'; //заявка (изменение)
    const EVENT_INVOICE_DELETE = 'ONCRMINVOICEDELETE'; //заявка (удаление)
    const EVENT_LEAD_CREATE = 'ONCRMLEADADD'; //лид (добавление)
    const EVENT_LEAD_UPDATE = 'ONCRMLEADUPDATE'; //лид (изменение)
    const EVENT_LEAD_DELETE = 'ONCRMLEADDELETE'; //лид (удаление)
    const EVENT_ACTIVITY_CREATE = 'ONCRMACTIVITYADD'; //дела (добавление)
    const EVENT_ACTIVITY_UPDATE = 'ONCRMACTIVITYUPDATE'; //дела (изменение)
    const EVENT_ACTIVITY_DELETE = 'ONCRMACTIVITYDELETE'; //дела (удаление)
    const EVENT_REQUISITE_CREATE = 'ONCRMREQUISITEADD'; //реквизит (добавление)
    const EVENT_REQUISITE_UPDATE = 'ONCRMREQUISITEUPDATE'; //реквизит (изменение)
    const EVENT_REQUISITE_DELETE = 'ONCRMREQUISITEDELETE'; //реквизит (удаление)
    const EVENT_TASK_CREATE = 'ONTASKADD'; //задача (добавление)
    const EVENT_TASK_UPDATE = 'ONTASKUPDATE'; //задача (изменение)
    const EVENT_TASK_DELETE = 'ONTASKDELETE'; //задача (удаление)
    const EVENT_CALENDAR_SECTION_CREATE = 'ONCALENDARSECTIONADD'; //секция календаря (создание)
    const EVENT_CALENDAR_SECTION_UPDATE = 'ONCALENDARSECTIONADD'; //секция календаря (изменение)
    const EVENT_CALENDAR_SECTION_DELETE = 'ONCALENDARSECTIONADD'; //секция календаря (удаление)
    const EVENT_CALENDAR_CREATE = 'ONCALENDARENTRYADD'; //событие календаря (добавление)
    const EVENT_CALENDAR_UPDATE = 'ONCALENDARENTRYUPDATE'; //событие календаря (изменение)
    const EVENT_CALENDAR_DELETE = 'ONCALENDARENTRYDELETE'; //событие календаря (удаление)

    const EVENTS = [
        self::EVENT_INVOICE_CREATE,
        self::EVENT_INVOICE_DELETE,
        //self::EVENT_INVOICE_STATUS,
        self::EVENT_INVOICE_UPDATE,
        self::EVENT_PRODUCT_CREATE,
        self::EVENT_PRODUCT_UPDATE,
        self::EVENT_PRODUCT_DELETE,
        self::EVENT_SECTION_CREATE,
        self::EVENT_SECTION_UPDATE,
        self::EVENT_SECTION_DELETE,
        //self::EVENT_LEAD_CREATE,
        //self::EVENT_LEAD_UPDATE,
        //self::EVENT_LEAD_DELETE,
        self::EVENT_DEAL_CREATE,
        self::EVENT_DEAL_UPDATE,
        self::EVENT_DEAL_DELETE,
        self::EVENT_COMPANY_CREATE,
        self::EVENT_COMPANY_UPDATE,
        self::EVENT_COMPANY_DELETE,
        self::EVENT_CONTACT_CREATE,
        self::EVENT_CONTACT_UPDATE,
        self::EVENT_CONTACT_DELETE,
        //self::EVENT_ACTIVITY_CREATE,
        //self::EVENT_ACTIVITY_UPDATE,
        //self::EVENT_ACTIVITY_DELETE,
        //self::EVENT_REQUISITE_CREATE,
        //self::EVENT_REQUISITE_UPDATE,
        //self::EVENT_REQUISITE_DELETE,
        //self::EVENT_TASK_CREATE,
        //self::EVENT_TASK_UPDATE,
        //self::EVENT_TASK_DELETE,
        //self::EVENT_CALENDAR_CREATE,
        //self::EVENT_CALENDAR_UPDATE,
        //self::EVENT_CALENDAR_DELETE,
        //self::EVENT_CALENDAR_SECTION_CREATE,
        //self::EVENT_CALENDAR_SECTION_UPDATE,
        //self::EVENT_CALENDAR_SECTION_DELETE,
    ];

    /** @var Client */
    private $_client;
    /** @var Request */
    private $_request;
    /** @var Response */
    private $_response;

    private $_token;

    /**
     * Отключает интеграцию от клиента, удаляет все имеющиеся данные
     *
     * @param Employee $employee
     */
    public static function disconnect(Employee $employee)
    {
        $condition = ['company_id' => $employee->company_id];
        Invoice::deleteAll($condition);
        DealPosition::deleteAll($condition);
        Deal::deleteAll($condition);
        Contact::deleteAll($condition);
        Company::deleteAll($condition);
        Product::deleteAll($condition);
        Vat::deleteAll($condition);
        Section::deleteAll($condition);
        Catalog::deleteAll($condition);
        User::deleteAll($condition);
        $employee->saveIntegration(Employee::INTEGRATION_BITRIX24, null);
    }

    /**
     * @param string $host
     * @return Response
     */
    public static function redirectToken(string $host)
    {
        $url = 'https://' . $host . '.bitrix24.ru/oauth/authorize/?client_id=' . urlencode(self::APPLICATION_ID) . '&response_type=code&redirect_url=' . urlencode(Url::to('integration/bitrix24/token',
                'https'));
        return Yii::$app->response->redirect($url);
    }

    /**
     * Возвращает URL получения токена OAuth 2.0
     *
     * @param string $code Код для получения токена
     * @return string
     */
    public static function getTokenURL(string $code): string
    {
        return 'https://oauth.bitrix.info/oauth/token/?grant_type=authorization_code&client_id=' . urlencode(self::APPLICATION_ID) . '&client_secret=' . self::APPLICATION_SECRET . '&code=' . $code;
    }

    /**
     * Возвращает URL обработчика веб-хуков
     *
     * @return string
     */
    public static function getHookURL(): string
    {
        return Url::to('/integration/bitrix24/hook', 'https');
    }

    public static function hook(string $memberId, string $event, int $id, Bitrix24Helper $helper)
    {
        $user = User::findByMemberId($memberId);
        if ($user === null) {
            return;
        }
        $companyId = $user->company_id;
        unset($user);

        $entity = substr($event, 2);
        $event = substr($entity, -6);
        if ($event === 'UPDATE' || $event === 'DELETE') {
            $entity = substr($entity, 0, -6);
            $event = 'hook' . ucfirst(strtolower($event));
        } else {
            $entity = substr($entity, 0, -3);
            $event = 'hookCreate';
        }
        if (substr($entity, 0, 3) === 'CRM') {
            $entity = substr($entity, 3);
        }
        if ($entity === 'PRODUCTSECTION') {
            $entity = 'Section';
        }
        $entity = '\frontend\modules\integration\models\bitrix24\\' . ucfirst(strtolower($entity));
        $entity = new $entity();
        $entity->$event($companyId, $id, $helper);
    }

    /**
     * Создаёт экземпляр класса на основе сохранённых данных авториации клиента КУБ
     *
     * @param Employee $employee
     * @return Bitrix24Helper
     * @throws InvalidConfigException
     */
    public static function instance(Employee $employee)
    {
        $config = $employee->integration(Employee::INTEGRATION_BITRIX24);
        if (is_array($config) === false) {
            return new self();
        }
        return new self($config['host'], $config['token']);
    }

    /**
     * @param string $domain Домен Битрикс24
     * @param string $token  Токен доступа
     * @throws InvalidConfigException
     */
    public function __construct(string $domain = null, string $token = null)
    {
        $this->_client = new Client();
        $this->_request = $this->_client->createRequest();
        if ($domain !== null) {
            $this->_client->baseUrl = 'https://' . $domain . '/';
        }
        if ($token) {
            $this->_token = $token;
        }
    }

    /**
     * Выполняет запрос к REST API
     *
     * @param string     $entity Имя запрошенной функции
     * @param array|null $data   Данные запроса
     * @return mixed|null
     * @throws Exception
     */
    public function rest(string $entity, array $data = null)
    {
        $url = 'rest/' . $entity;
        if ($data !== null) {
            $url .= '?' . self::_buildQuery($data);
        }
        return $this->post($url, []);
    }

    /**
     * Отправляет POST-запрос на сервер Битрикс24
     *
     * @param       $url
     * @param array $data
     * @return mixed|null
     * @throws Exception
     */
    public function post($url, array $data)
    {
        $this->_request->method = 'POST';
        $this->_request->url = $url;
        $this->_request->data = $data;
        return $this->_send();
    }

    /**
     * Устанавливает обработчики веб-хуков
     *
     * @return bool
     * @throws Exception
     */
    public function setHooks(): bool
    {
        $data = $this->rest('event.get');
        if ($data === null) {
            return false;
        }
        foreach ($data as &$item) {
            $item = $item['event'];
        }
        $hook = array_diff(self::EVENTS, $data);
        if (count($hook) < 1) {
            return true;
        }
        unset($data);
        $handler = self::getHookURL();
        $batch = ['halt' => 0, 'cmd' => []];
        foreach ($hook as $item) {
            $batch['cmd'][$item] = 'event.bind?' . http_build_query([
                    'event' => $item,
                    'handler' => $handler,
                ]);
        }
        $result = $this->rest('batch.json', $batch);
        return $result !== null;
    }


    /**
     * @return mixed|null
     * @throws Exception
     */
    private function _send()
    {
        if ($this->_token !== null) {
            if (strpos($this->_request->url, '?') !== false) {
                $separator = '&';
            } else {
                $separator = '?';
            }
            $this->_request->url .= $separator . 'auth=' . $this->_token . '&auth_type=0';
        }
        $this->_response = $this->_request->send();
        if ($this->_response->statusCode != 200 && $this->_response->statusCode != 204) {
            return null;
        }
        $this->_request->setCookies($this->_response->getCookies());
        $data = json_decode($this->_response->content, true) ?? null;
        if (isset($data['result']) === true) {
            return $data['result'];
        }
        return $data;
    }

    /**
     * Почти тоже, что http_build_query, но не кодирует ключи
     *
     * @param array $data Ассоциативный массив параметров
     * @param null  $prefix
     * @return string
     */
    private static function _buildQuery(array $data, $prefix = null)
    {
        $url = '';
        foreach ($data as $key => $item) {
            if ($url !== '') {
                $url .= '&';
            }
            if (is_array($item) === true) {
                $url .= self::_buildQuery($item, $key);
            } else {
                if ($prefix !== null) {
                    $key = $prefix . '[' . $key . ']';
                }
                $url .= $key . '=' . urlencode($item);
            }
        }
        return $url;
    }

}