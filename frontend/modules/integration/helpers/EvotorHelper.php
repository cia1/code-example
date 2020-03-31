<?php

namespace frontend\modules\integration\helpers;

use common\models\employee\Employee;
use common\models\evotor\User;
use frontend\modules\integration\models\evotor\Device;
use frontend\modules\integration\models\evotor\Employee as EmployeeEvotor;
use frontend\modules\integration\models\evotor\HookTrait;
use frontend\modules\integration\models\evotor\Product;
use frontend\modules\integration\models\evotor\Receipt;
use frontend\modules\integration\models\evotor\Store;
use yii\base\InvalidConfigException;
use yii\httpclient\Client;
use yii\httpclient\Exception;
use yii\httpclient\Request;
use yii\httpclient\Response;

/**
 * Помощник по работе с Эвотор
 *
 * @see /doc/integrations.md
 */
class EvotorHelper
{

    const APPLICATION_NAME = 'КУБ-24';
    const APPLICATION_TOKEN = 'wpeo98uj23048h3208h';
    const BASE_URI = 'https://api.evotor.ru/';

    /** @var Client */
    private $_client;
    /** @var Request */
    private $_request;
    /** @var Response */
    private $_response;

    private $_pagination;

    /**
     * Отключает интеграцию от клиента, удаляет все имеющиеся данные
     *
     * @param Employee $employee
     */
    public static function disconnect(Employee $employee)
    {
        $condition = ['company_id' => $employee->company_id];
        User::deleteAll($condition);
        Receipt::deleteAll($condition);
        Product::deleteAll($condition);
        EmployeeEvotor::deleteAll($condition);
        Device::deleteAll($condition);
        Store::deleteAll($condition);
        $employee->saveIntegration(Employee::INTEGRATION_EVOTOR, null);
    }

    /**
     * Обработчик веб-хука, контроллер передаёт управление сюда
     *
     * @param string      $action        Запрошенное действие, настраивается в приложении эвотора
     * @param mixed       $data          Данные веб-хука
     * @param string|null $path          URL-адрес веб-хука, может содержать дополнительные данные
     * @param null        $authorization HTTP-заголовок "Authorization"
     * @return bool|null
     */
    public static function hook(string $action, $data, string $path = null, $authorization = null)
    {
        $authorization = explode(':', substr($authorization, 7));
        if (self::APPLICATION_TOKEN !== $authorization[0]) {
            return null;
        }
        $companyId = isset($authorization[1]) === true ? (int)($authorization[1]) : null;
        //Если есть кастомный обработчик
        $selfAction = 'hook' . ucfirst($action);
        if (method_exists(static::class, $selfAction) === true) {
            return static::$selfAction($data);
        }
        unset($selfAction);
        if ($companyId === null) {
            return null;
        }
        $class = '\frontend\modules\integration\models\\evotor\\' . ucfirst($action);
        if (class_exists($class) === false) {
            return null;
        }
        $class = new $class;
        if (in_array(HookTrait::class, class_uses($class)) === false) {
            return null;
        }
        /** @var HookTrait $class */
        return $class->hook($companyId, $data, $path ?? '');
    }

    /**
     * Чеки почему-то с декабря 2019-го стали авторизовываться иначе.
     *
     * @param $data
     * @return mixed
     */
    protected function hookReceipt($data)
    {
        $companyId = EmployeeEvotor::find()->select('company_id')->where(['uuid' => $data['data']['employeeId']])->asArray()->one();
        if ($companyId === null) {
            return null;
        } else {
            $companyId = (int)$companyId['company_id'];
        }
        $class = new Receipt();
        return $class->hook($companyId, $data, '');
    }

    /**
     * Веб-хук авторизации и регистрации пользователя
     *
     * @param $data
     * @return array|null
     */
    protected static function hookAuth($data)
    {
        $employee = Employee::findIdentityByLogin($data['login']);
        if ($employee === null) {
            return null;
        }
        if ($employee->validatePassword($data['password']) === false) {
            return null;
        }
        //Регистрация пользователя Эвотор (пока без токена доступа REST API)
        $user = User::findOne([
            'OR',
            ['company_id' => $employee->company_id],
            ['id' => $data['userId']]
        ]);
        if ($user === null) {
            $user = new User();
        }
        $user->id = $data['userId'];
        $user->company_id = $employee->company_id;
        $user->token = null;
        if ($user->save() === false) {
            return null;
        }
        $employee->saveIntegration(Employee::INTEGRATION_EVOTOR, true);
        return [
            'userId' => $data['userId'],
            'token' => EvotorHelper::APPLICATION_TOKEN . ':' . $employee->id,
        ];
    }

    /**
     * Веб-хук получения токена после регистрации
     *
     * @param array $data Данные веб-хука
     * @return null|string
     * @throws Exception
     * @throws InvalidConfigException
     */
    protected static function hookToken($data)
    {
        $user = User::findOne(['id' => $data['userId']]);
        if ($user === null) {
            return null;
        }
        $user->token = $data['token'];
        $user->save();
        $helper = new self($user->token);
        //Загрузить магазины
        if (Store::find()->where(['company_id' => $user->company_id])->count() < 1) { //возможно, уже загружено ранее
            $out = $helper->get('stores');
            $stores = [];
            foreach ($out ?? [] as $item) {
                $model = new Store();
                $model->hook($user->company_id, $item, '');
                $stores[] = $model;
            }
        } else {
            $stores = Store::findAll(['company_id' => $user->company_id]);
        }
        //Загрузить терминалы
        if (Device::find()->where(['company_id' => $user->company_id])->count() < 1) { //возможно, уже загружено ранее
            $out = $helper->get('devices');
            foreach ($out ?? [] as $item) {
                $model = new Device();
                $model->hook($user->company_id, $item, '');
            }
        }
        //Загрузить сотрудников
        if (EmployeeEvotor::find()->where(['company_id' => $user->company_id])->count() < 1) { //возможно, уже загружено ранее
            $out = $helper->get('employees');
            foreach ($out ?? [] as $item) {
                $model = new EmployeeEvotor();
                $model->hook($user->company_id, $item, '');
            }
        }
        //Загрузить номенклатуру (если это возможно, см. docs/integrations.md)
        foreach ($stores as $store) {
            //$products = $helper->get('stores/' . $store->UUID . '/products'); //v2 не работает, используем v1
            $products = $helper->get('api/v1/inventories/stores/' . $store->uuid . '/products') ?? [];
            Product::deleteAll(['store_id' => $store->id]);
            foreach ($products as $item) {
                self::_createProduct($user->company_id, $store->id, $item, $products);
            }
        }
        return 'OK';
    }

    /**
     * EvotorHelper constructor.
     *
     * @param string $token Токен доступа к REST API
     * @throws InvalidConfigException
     */
    public function __construct(string $token)
    {
        $this->_client = new Client();
        $this->_client->baseUrl = self::BASE_URI;
        $this->_request = $this->_client->createRequest();
        $this->_request->setHeaders([
            'Content-Type' => 'application/vnd.evotor.v2+bulk+json',
            'Accept' => 'application/vnd.evotor.v2+json',
            'Authorization' => $token,
        ]);
    }

    /**
     * Выполняет GET-запрос
     *
     * @param string     $url  Относительный URL (часть после self::BASE_URI)
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
        $this->_request->url = self::BASE_URI . $url;
        return $this->_send();
    }

    /**
     * @return mixed|null
     * @throws Exception
     */
    private function _send()
    {
        $this->_response = $this->_request->send();
        $this->_request->setCookies($this->_response->getCookies());
        if ($this->_response->statusCode != 200 && $this->_response->statusCode != 204) {
            return null;
        }
        $data = json_decode($this->_response->content, true) ?? null;
        if (is_array($data) === true) {
            if (isset($data['paging']) === true) {
                $this->_pagination = $data['paging'];
            }
            if (isset($data['items']) === true) {
                $data = $data['items'];
            }
        }
        return $data;
    }

    /**
     * @param int   $companyId
     * @param int   $storeId
     * @param array $item
     * @param array $data
     * @return Product|null
     */
    private static function _createProduct(int $companyId, int $storeId, array $item, array $data)
    {
        $uuid = $item['parent_id'] ?? $item['parentUuid'];
        if ($uuid) {
            $parentId = Product::idByUUID($uuid);
            if ($parentId === null) {
                $product = self::_createProduct($companyId, $storeId, self::_popByUUID($uuid, $data), $data);
                if ($product === null) {
                    return null;
                }
                $parentId = $product->parent_id;
            }
            $item['parent_id'] = $parentId;
        } else {
            $item['parentId'] = null;
        }
        $product = new Product();
        $product->store_id = $storeId;
        $product->hook($companyId, $item, '');
        return $product;
    }


    private static function _popByUUID(string $uuid, array $data)
    {
        foreach ($data as $item) {
            if ($item['uuid'] === $uuid) {
                return $item;
            }
        }
        return null;
    }

}