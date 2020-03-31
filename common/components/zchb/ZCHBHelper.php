<?php

namespace common\components\zchb;

use Yii;
use yii\base\ErrorException;
use yii\caching\FileCache;

/**
 * Помощник доступа к API ЗАЧЕСТНЫЙБИЗНЕС.
 * Кэширует каждый запрос на время, указанное в self::CACHE_TIMEOUT
 *
 * @see https://zachestnyibiznesapi.ru
 *
 */
class ZCHBHelper
{

    const CACHE_TIMEOUT = 3600 * 24 * 30; //Время актуальности кэша

    const BASE_URL = 'https://zachestnyibiznesapi.ru/paid/data/';

    /**
     * Валидные статусы с "пустым" ответом. Не ошибка, но нет данных для возврата из API.
     *
     * @see https://zachestnyibiznesapi.ru/#answer-code-status-list
     */
    const STATUS_EMPTY_RESULTS = [219, 220, 221, 222, 223, 224, 235];

    /**
     * @var int|null TIMESTAMP, кода запрошенные данные были сохранены в кеше
     * @see self::cacheTime()
     * @see self::request()
     */
    protected $cacheTime;

    /**
     * Возвращает кэширующий объект
     * Yii::$app->cache не подходит, т.к. этот кэш может быть очищен в любое время.
     * @return FileCache
     */
    public static function cacher(): FileCache
    {
        static $cache;
        if ($cache === null) {
            $f = Yii::getAlias('@runtime/zchb/');
            if (is_dir($f) === false) {
                mkdir($f);
            }
            /** @var FileCache $cache */
            $cache = new FileCache();
            $cache->directoryLevel = 0;
            $cache->cachePath = $f;
        }
        return $cache;
    }

    /**
     * @param bool $useCache
     * @return static
     * @throws ZCHBAPIException
     */
    public static function instance(bool $useCache = true)
    {
        static $self;
        if ($self === null) {
            $apiKey = Yii::$app->params['zachestniybiznes_api_key'] ?? null;
            if (is_string($apiKey) === false) {
                throw new ZCHBAPIException('API-ключ доступа не определён');
            }
            $self = new static($apiKey, $useCache);
        }
        return $self;
    }

    public function __construct(string $apiKey, bool $useCache = true)
    {
        $this->_apiKey = $apiKey;
        $this->_useCache = $useCache;
    }

    /** @var string API-ключ доступа */
    private $_apiKey;
    /** @var bool Использовать ли кеш */
    public $_useCache = true;

    /**
     * Возвращает первый найденный элемент (документ) согласно запросу
     *
     * @param string   $method
     * @param array    $params
     * @param callable $filter Функция, выполняющая обработку данных перед сохранением в кэше
     * @return array|null
     * @throws ZCHBAPIException
     */
    public function requestOne(string $method, array $params = [], callable $filter = null)
    {
        $data = $this->request($method, $params, function ($data) use ($filter) {
            if ($data === null) {
                return null;
            }
            if (isset($data['docs']) === true) {
                $data = $data['docs'][0];
            }
            return $filter($data);
        });
        return $data;
    }

    /**
     * Выполняет запрос к API или возвращает данные из кеша
     *
     * @param string   $method
     * @param array    $params
     * @param callable $filter Функция, выполняющая обработку данных перед сохранением в кэше
     * @return array|null
     * @throws ZCHBAPIException
     */
    public function request(string $method, array $params = [], callable $filter = null)
    {
        $this->cacheTime = null;
        $params = http_build_query($params);
        $key = $method . md5($params);
        if ($this->_useCache === true) {
            $data = $this->_cacheGet($key);
        } else {
            $data = false;
        }
        if ($data === false) {
            try {
                $data = $this->makeRequest($method, $params);
                if ($filter !== null) {
                    $data = $filter($data);
                }
            } catch (ZCHBAPIException $e) {
                throw $e;
            }
            $this->cacheTime = time();
            $this->_cacheSet($key, $data);
        }
        return $data;
    }

    /**
     * Возвращает дату и время актуальности последнего запрошенного кеша
     *
     * @return int|null
     */
    public function cacheTime()
    {
        return $this->cacheTime;
    }

    /**
     * Выполняет запрос к API
     *
     * @param string $method
     * @param string $params
     * @return array|null
     * @throws ZCHBAPIException
     */
    protected function makeRequest(string $method, string $params)
    {
        if ($params) {
            $params .= '&';
        }
        $params .= 'api_key=' . $this->_apiKey;
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'content' => $params,
            ],
        ]);
        try {
            $result = file_get_contents(self::BASE_URL . $method, false, $context);
        } catch (ErrorException $e) {
            throw new ZCHBAPIException('Нет доступа к API ЗАЧЕСТНЫЙБИЗНЕС', $method);
        }
        if ($result === false) {
            throw new ZCHBAPIException('Нет доступа к API ЗАЧЕСТНЫЙБИЗНЕС', $method);
        }
        /** @see https://zachestnyibiznesapi.ru/#answer-format Формат ответа */
        $result = json_decode($result, true);
        if (is_array($result) === false) {
            throw new ZCHBAPIException('Неожиданный формат ответа', $method);
        }

        $result['status'] = (int)$result['status'];
        if ($result['status'] === 200) {
            return $result['body'];
        }
        if (in_array($result['status'], self::STATUS_EMPTY_RESULTS) === false) {
            throw new ZCHBAPIException($result['message'], $method, $params);
        }
        return null;
    }

    /**
     * Кэш часто очищается, поэтому для ЗАЧЕСТНЫЙБИЗНЕС используем другой директорий
     * @param string $key
     * @return mixed
     */
    private function _cacheGet(string $key)
    {
        $data = self::cacher()->get($key);
        if (is_array($data) === true) {
            list($this->cacheTime, $data) = $data;
        }
        return $data;
    }

    private function _cacheSet(string $key, $data)
    {
        self::cacher()->set($key, [$this->cacheTime, $data], self::CACHE_TIMEOUT);
    }

}
