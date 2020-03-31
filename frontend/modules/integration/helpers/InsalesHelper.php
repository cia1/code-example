<?php

namespace frontend\modules\integration\helpers;

use common\models\employee\Employee;
use common\models\insales\Account;
use frontend\modules\integration\models\insales\Client;
use frontend\modules\integration\models\insales\Order;
use frontend\modules\integration\models\insales\OrderPosition;
use frontend\modules\integration\models\insales\Product;
use frontend\modules\integration\models\insales\ShippingAddress;
use yii\base\InvalidConfigException;
use yii\helpers\Url;
use yii\httpclient\Client as ClientHttp;
use yii\httpclient\Exception;
use yii\httpclient\Request;
use yii\web\Response;

/**
 * Помощник интеграции In Sales CMS
 */
class InsalesHelper
{
    const APPLICATION_NAME = 'КУБ-24'; //отображаемое на сайте название приложения
    const APPLICATION_ID = 'kub-24'; //Идентификатор приложения, задаётся в приложении
    const APPLICATION_SECRET = '3638d6686e1b394ead3f52a7e41af6d5'; //Должен соответствовать "секрет" приложения InSales

    const WEB_HOOKS = [
        'orders/create',
        'orders/update',
        'orders/destroy',
    ];
    const HOOK_URL = 'integration/insales/hook';

    /**
     * Обработчик веб-хука установки приложения
     * Созадаёт запись в {{insales_account}}, но до авторизации информация не полная
     *
     * @param int    $id    Идентификатор магазина
     * @param string $host  Домен магазина
     * @param string $token Токен, на основе которого нужно создать пароль доступа к API
     */
    public static function install(int $id, string $host, string $token)
    {
        $account = Account::findOne(['id' => $id]);
        if ($account === null) {
            $account = new Account();
            $account->id = $id;
            $account->company_id=null;
        }
        $account->shop = $host;
        $account->password = self::getPassword($token);
        $account->save();
    }

    /**
     * Возвращает пароль доступа к API на основе токена доступа
     *
     * @param string $token
     * @return string|null
     */
    public static function getPassword(string $token)
    {
        if (strlen($token) !== 32) {
            return null;
        }
        return md5($token . self::APPLICATION_SECRET);
    }

    /**
     * Устанавливает веб-хуки, если они ещё не были установлены
     *
     * @param Account $account
     * @throws InvalidConfigException
     */
    public static function setWebHook(Account $account)
    {
        $helper = new static($account->shop, $account->password);
        $webhooks = $helper->get('webhooks') ?? [];
        foreach ($webhooks as $i => $item) {
            $webhooks[$i] = $item['topic'];
        }
        foreach (static::WEB_HOOKS as $hook) {
            if (in_array($hook, $webhooks) === true) {
                continue;
            }
            $url = Url::home(true) . self::HOOK_URL;
            $helper->post('webhooks', [
                'webhook' => [
                    'address' => $url,
                    'topic' => $hook,
                    'format_type' => 'json',
                ],
            ]);
        }
    }

    /**
     * Обработчик веб-хука, контроллер передаёт управление сюда
     *
     * @param array $data Данные, полученные от удалённого сервера
     */
    public static function hook(array $data)
    {
        //Найти ID клиента КУБ
        $account = Account::findOne(['id' => $data['account_id']]);
        if ($account === null) {
            return;
        }
        //Найти или создать клиента InSales
        if (isset($data['client']) === true) {
            $client = Client::createOrUpdate($data['client']['id'], $account->company_id, $data['client']);
            if ($client === null) {
                return;
            }
            unset($data['client']);
        } else {
            return;
        }
        //Найти или создать адрес доставки
        if (isset($data['shipping_address']) === true) {
            $shippingAddress = ShippingAddress::createOrUpdate($data['shipping_address']['id'], $client->id ?? null, $data['shipping_address']);
            if ($shippingAddress === null) {
                return;
            }
            unset($data['shipping_address']);
        } else {
            $shippingAddress = null;
        }
        //Найти или создать заказ
        $data['custom_status'] = $data['custom_status']['title'] ?? ($data['custom_status'] ?? null);
        if ($data['custom_status'] === null) {
            unset($data['custom_status']);
        }
        $order = Order::createOrUpdate($data['id'], $account->company_id, $client->id, $shippingAddress->id ?? null, $data);
        if ($order === null) {
            return;
        }
        //Создание товарных позиций
        foreach ($data['order_lines'] as $item) {
            $product = Product::createOrUpdate($item['product_id'], $account->company_id, $item);
            if ($product === null) {
                continue;
            }
            OrderPosition::createOrUpdate($item['id'], $order->id, $product->id, $item);
        }
    }

    /**
     * Отключает интеграцию от клиента, удаляет все имеющиеся данные
     *
     * @param Employee $employee
     */
    public static function disconnect(Employee $employee)
    {
        $condition = ['company_id' => $employee->company_id];
        Client::deleteAll($condition);
        Account::deleteAll($condition);
        Product::deleteAll($condition);
        $employee->saveIntegration(Employee::INTEGRATION_INSALES, null);
    }

    /** @var ClientHttp */
    private $_client;
    /** @var Request */
    private $_request;
    /** @var Response */
    private $_response;

    /**
     * @param string $host
     * @param string $password
     * @throws InvalidConfigException
     */
    public function __construct(string $host, string $password)
    {
        $this->_client = new ClientHttp();
        $this->_client->baseUrl = 'http://' . $host . '/admin/';
        $this->_request = $this->_client->createRequest();
        $this->_request->setHeaders([
            'Content-Type' => 'application/json; charset=UTF-8',
            'Authorization' => 'Basic ' . base64_encode(self::APPLICATION_ID . ':' . $password),
            'Accept' => 'application/json',
        ]);
    }

    /**
     * Выполняет GET-запрос
     *
     * @param string $url Часть URL, после "/admin/"
     * @return array|null
     * @throws Exception
     */
    public function get(string $url)
    {
        $this->_request->method = 'GET';
        $this->_request->setUrl($url . '.json');
        return $this->_send();
    }

    /**
     * Выполняет POST-запрос
     *
     * @param string $url
     * @param array  $data
     * @return mixed|null
     * @throws Exception
     */
    public function post(string $url, array $data)
    {
        $this->_request->method = 'POST';
        $this->_request->data = $data;
        $this->_request->setUrl($url . '.json');
        return $this->_send();
    }

    /**
     * @return mixed|null
     * @throws Exception
     */
    private function _send()
    {
        $this->_request->setFormat('json');
        $this->_response = $this->_request->send();
        $this->_request->setCookies($this->_response->getCookies());
        if ($this->_response->statusCode != 200 && $this->_response->statusCode != 201) {
            return null;
        }
        $data = json_decode($this->_response->content, true) ?? null;
        return $data;
    }

}