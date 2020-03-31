<?php

namespace frontend\modules\integration\helpers;

use common\models\amocrm\Contact;
use common\models\amocrm\Customer;
use common\models\amocrm\Note;
use common\models\amocrm\Task;
use common\models\amocrm\User;
use common\models\employee\Employee;
use frontend\modules\integration\models\amocrm\HookTrait;
use frontend\modules\integration\models\amocrm\Lead;
use Yii;
use yii\base\InvalidArgumentException;
use yii\httpclient\Client;
use yii\httpclient\Response;
use yii\web\ForbiddenHttpException;
use yii\httpclient\Exception;
use yii\base\InvalidConfigException;

class AMOcrmHelper
{

    const EVENT_ADD_LEAD = 'add_lead'; //сделка
    const EVENT_ADD_CONTACT = 'add_contact'; //контакт
    const EVENT_ADD_COMPANY = 'add_company';
    const EVENT_ADD_CUSTOMER = 'add_customer';
    const EVENT_ADD_TASK = 'add_task';
    const EVENT_UPDATE_LEAD = 'update_lead';
    const EVENT_UPDATE_CONTACT = 'update_contact';
    const EVENT_UPDATE_COMPANY = 'update_company';
    const EVENT_UPDATE_CUSTOMER = 'update_customer';
    const EVENT_UPDATE_TASK = 'update_task';
    const EVENT_DELETE_LEAD = 'delete_lead';
    const EVENT_DELETE_CONTACT = 'delete_contact';
    const EVENT_DELETE_COMPANY = 'delete_company';
    const EVENT_DELETE_CUSTOMER = 'delete_customer';
    const EVENT_DELETE_TASK = 'delete_task';
    const EVENT_STATUS_LEAD = 'status_lead';
    const EVENT_NOTE_LEAD = 'note_lead';
    const EVENT_NOTE_CONTACT = 'note_contact';
    const EVENT_NOTE_COMPANY = 'note_company';
    const EVENT_NOTE_CUSTOMER = 'note_customer';
    //Список поддерживаемых веб-хуков
    const SUPPORTED_EVENTS = [
        self::EVENT_ADD_LEAD,
        self::EVENT_ADD_CONTACT,
        self::EVENT_ADD_COMPANY,
        self::EVENT_ADD_CUSTOMER,
        self::EVENT_ADD_TASK,
        self::EVENT_UPDATE_LEAD,
        self::EVENT_UPDATE_CONTACT,
        self::EVENT_UPDATE_COMPANY,
        self::EVENT_UPDATE_CUSTOMER,
        self::EVENT_UPDATE_TASK,
        self::EVENT_DELETE_LEAD,
        self::EVENT_DELETE_CONTACT,
        self::EVENT_DELETE_COMPANY,
        self::EVENT_DELETE_CUSTOMER,
        self::EVENT_DELETE_TASK,
        self::EVENT_STATUS_LEAD,
        self::EVENT_NOTE_LEAD,
        self::EVENT_NOTE_CONTACT,
        self::EVENT_NOTE_COMPANY,
        self::EVENT_NOTE_CUSTOMER,
    ];

    const VALID_HOOKS = ['add', 'update', 'delete', 'note'];

    /**
     * Отключает интеграцию от клиента, удаляет все имеющиеся данные
     *
     * @param Employee $employee
     */
    public static function disconnect(Employee $employee)
    {
        $condition = ['company_id' => $employee->company_id];
        Contact::deleteAll($condition);
        Customer::deleteAll($condition);
        Lead::deleteAll($condition);
        Note::deleteAll($condition);
        Task::deleteAll($condition);
        User::deleteAll($condition);
        $employee->company->saveIntegration(Employee::INTEGRATION_AMOCRM, null);
    }

    /**
     * Обработчик веб-хука, контроллер передаёт сюда управление
     *
     * @param string $entity Имя сущности, к которой относится событие
     * @param string $action ИМя события
     * @param mixed  $data   Сопутствующие данные
     * @param int    $userId Идентификатор пользователя AMOcrm
     */
    public static function hook(string $entity, string $action, $data, int $userId)
    {
        if (in_array($action, self::VALID_HOOKS) === false) {
            return;
        }
        //В AMOcrm часть сущностей во множестенном числе, часть - в единственном
        if (substr($entity, -1) === 's') {
            $entity = substr($entity, 0, -1);
        }
        $class = '\frontend\modules\integration\models\\amocrm\\' . ucfirst($entity);
        if (class_exists($class) === false) {
            return;
        }
        $class = new $class;
        if (in_array(HookTrait::class, class_uses($class)) === false) {
            return;
        }
        //Если есть кастомный обработчик (метод в этом классе)...
        $action = 'hook' . ucfirst($entity) . ucfirst($action);
        if (method_exists(static::class, $action) === true) {
            foreach ($data as $item) {
                static::$action($class, $item, $userId);
            }
        } else { //...если его нет...
            $action = 'hook' . ucfirst($action);
            foreach ($data as $item) {
                $class->$action($userId, $item);
            }
        }
    }

    private $_client;
    /** @varF Request */
    private $_request;
    /** @var Response */
    private $_response;

    /**
     * @param string $login  Логин
     * @param string $apiKey Ключ API
     * @param string $host   Субдомен клиента (XXX.amocrm.ru)
     * @throws Exception
     * @throws ForbiddenHttpException
     * @throws InvalidConfigException
     */
    public function __construct(string $login, string $apiKey, string $host)
    {
        $this->_client = new Client();
        $this->_client->baseUrl = 'https://' . $host . '.amocrm.ru';
        $this->_request = $this->_client->createRequest();
        $this->_auth($login, $apiKey);
        $this->_client->baseUrl .= '/api/v2/';
    }

    /**
     * Возвращает список установленных веб-хуков
     *
     * @return array|null
     * @throws Exception
     */
    public function getHookList()
    {
        $data = $this->get('webhooks')['items'] ?? null;
        if ($this->_response->statusCode == 204) {
            return [];
        }
        return $data;
    }

    /**
     * Устанавливает веб-хук.
     *
     * @param string $backUrl URL-обработчика веб-хуков
     * @param array  $events  Список событий, на которые будет реагировать обработчик, @see self::SUPPORTED_EVENTS
     * @return bool TRUE, если удалось установить обработчик, FALSE, если нет.
     * @throws Exception
     */
    public function setHook(string $backUrl, array $events): bool
    {
        foreach ($events as $item) {
            if (in_array($item, self::SUPPORTED_EVENTS) === false) {
                throw new InvalidArgumentException('Unsupported AMOcrm event "' . $item . '"');
            }
        }
        $request = clone($this->_request);
        $data = $this->post('webhooks/subscribe', [
            'subscribe' => [
                [
                    'url' => $backUrl,
                    'events' => $events,
                ],
            ],
        ]);
        if ($this->_response->statusCode != 200 || $data['items'][0]['result'] != true) {
            return false;
        }
        $this->_request = $request;
        $data = $this->get('account');
        $userId = $data['id'];
        $this->_createUserId($userId);
        return true;
    }

    /**
     * Кастомная обработка события изменения сделки
     * Веб-хук не содержит всех необходимых данных, поэтому нужно выполнить GET-запрос для их получения
     *
     * @param Lead  $class
     * @param mixed $data
     * @param int   $userId
     * @throws Exception
     * @throws ForbiddenHttpException
     * @throws InvalidConfigException
     */
    protected static function hookLeadUpdate(Lead $class, $data, int $userId)
    {
        $config = User::findOne(['user_id' => $userId])->company->integration(Employee::INTEGRATION_AMOCRM);
        $helper = new static($config['login'], $config['apiKey'], $config['host']);
        $lead = $helper->get('leads', ['id' => $data['id']])['items'][0] ?? null;
        $id = $lead['main_contact']['id'] ?? null;
        if ($id !== null) {
            $data['main_contact_id'] = $id;
        }
        $id = $lead['company']['id'] ?? null;
        if ($id !== null) {
            $data['contact_id'] = $id;
        }
        $class->hookUpdate($userId, $data);
    }

    /**
     * Возвращает описание последней произошедшей ошибки
     *
     * @return string
     */
    public function getError()
    {
        $content = json_decode($this->_response->content, true);
        return $content['response']['error'] ?? 'Не могу подключиться к серверу AMOcrm';
    }

    /**
     * Выполняет POST-запрос
     *
     * @param string $url  Относительный URL (часть после https://XXX.amocrm.ru/api/v2/)
     * @param array  $data POST-данные
     * @return mixed|null
     * @throws Exception
     */
    public function post(string $url, array $data)
    {
        $this->_request->method = 'POST';
        $this->_request->data = $data;
        $this->_request->setUrl($url);
        return $this->_send();
    }

    /**
     * Выполняет GET-запрос
     *
     * @param string     $url  Относительный URL (часть после https://XXX.amocrm.ru/api/v2/)
     * @param array|null $data Дополнительные GET-параметры
     * @return mixed|null
     * @throws Exception
     */
    public function get(string $url, array $data = null)
    {
        $this->_request->method = 'GET';
        if ($data !== null) {
            $url .= '?' . http_build_query($data);
        }
        $this->_request->url = $url;
        return $this->_send();
    }


    /**
     * Привязывает пользователя AMOcrm к авторизованному покупателю
     *
     * @param int $userId
     */
    private function _createUserId(int $userId)
    {
        User::deleteAll(['user_id' => $userId]); //связи СУБД очистят всю связанную информацию
        $model = new User();
        $model->user_id = $userId;
        /** @var Employee $employee */
        $employee=Yii::$app->user->identity;
        $model->company_id = $employee->company_id;
        $model->save();
    }

    /**
     * Выполняет авторизацию на удалённом сервере и сохраняет авторизацию в сессии.
     * Выбрасывает исключение ForbiddenHttpException, если авторизация не удалась.
     *
     * @param string $login  Логин
     * @param string $apiKey API ключ
     * @throws Exception
     * @throws ForbiddenHttpException
     */
    private function _auth(string $login, string $apiKey)
    {
        $response = $this->post('/private/api/auth.php', [
            'USER_LOGIN' => $login,
            'USER_HASH' => $apiKey,
        ]);
        $response = $response['auth'] ?? null;
        if ($response !== true) {
            throw new ForbiddenHttpException($this->getError());
        }
    }

    /**
     * @return mixed|null
     * @throws Exception
     */
    private function _send()
    {
        $url = $this->_request->url;
        $url .= (strrpos($url, '?') === false ? '?' : '&') . 'type=json';
        $this->_request->url = $url;
        $this->_prepare();
        $this->_response = $this->_request->send();
        $this->_request->setCookies($this->_response->getCookies());
        if ($this->_response->statusCode != 200 && $this->_response != 204) {
            return null;
        }
        $data = json_decode($this->_response->content, true) ?? null;
        if (is_array($data) === true) {
            if (isset($data['_embedded']) === true) {
                $data = $data['_embedded'];
            } elseif (isset($data['response']) === true) {
                $data = $data['response'];
            }
        }
        return $data;
    }

    private function _prepare()
    {
        $this->_request->setHeaders([
            'user-agent' => 'amoCRM-API-client/1.0',
            'content-Type' => 'application/json',
            'Accept' => 'application/json',
        ]);
    }

}