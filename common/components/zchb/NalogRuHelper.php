<?php

namespace common\components\zchb;

use yii\httpclient\Client;
use yii\httpclient\Response;
use yii\base\InvalidConfigException;
use yii\httpclient\Exception;

/**
 * Помощник доступа к pb.nalog.ru
 * Это не общедоступный API, поэтому есть неявные ограничения: допустимо не более одного запроса в 4-5 секунд.
 */
class NalogRuHelper
{

    const MODE_CMP = 'quick-cmp'; //организации
    const MODE_ADDR = 'quick-addr'; //адреса нескольких ЮЛ
    const MODE_TOKEN = 'quick-token'; //запрос дополнительных данных по полученному предыдущим запросом токену

    /** @varF Request */
    private $_request;
    /** @var Response */
    private $_response;

    const BASE_URL = 'https://pb.nalog.ru/';

    /**
     * @throws InvalidConfigException
     */
    public function __construct()
    {
        $client = new Client();
        $client->baseUrl = self::BASE_URL;
        $this->_request = $client->createRequest();
    }

    /**
     * Загружает карточку юридического лица (возвращает первую найденную)
     * @param string $id ИНН или название организации
     * @return array|null
     * @throws ZCHBAPIException
     */
    public function card(string $id)
    {
        $data = $this->_post('search-proc.json', [
                'mode' => self::MODE_CMP,
                'query' => $id
            ])['cmp']['data'] ?? null;
        if (is_array($data) === false) {
            throw new ZCHBAPIException('Nalog. Cannot parse card response', 'serach-proc.json', 'mode=' . self::MODE_CMP . '&query=' . $id);
        }
        if (count($data) === 0) {
            return null;
        }
        return $this->cardByToken($data[0]['token']);
    }

    /**
     * Загружает карточку юридического лица по полученному ранее токену
     * @param string $token
     * @return array
     * @throws ZCHBAPIException
     */
    public function cardByToken(string $token)
    {
        $data = $this->_post('company-proc.json', [
            'mode' => self::MODE_TOKEN,
            'token' => $token
        ]);
        if (is_array($data) === false) {
            throw new ZCHBAPIException('Nalog. Cannot parse card response', 'company-proc.json', 'mode=' . self::MODE_CMP . '&token=' . $token);
        }
        return $data;
    }

    /**
     * @param string     $url
     * @param array|null $post
     * @return array
     * @throws ZCHBAPIException
     */
    private function _post(string $url, array $post = null): array
    {
        $this->_request->setMethod('POST');
        $this->_request->setUrl($url);
        $this->_request->setData($post);
        $this->_request->setHeaders([
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Accept' => 'application/json',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/79.0.3945.130 Safari/537.36',
        ]);
        try {
            $this->_response = $this->_request->send();
        } catch (Exception $e) {
            throw new ZCHBAPIException('Nalog. ' . $e->getMessage(), $url, $post);
        }
        $this->_request->setCookies($this->_response->getCookies());
        $response = json_decode($this->_response->content, true) ?? null;
        if (is_array($response) === false && ($this->_response->statusCode < 200 || $this->_response->statusCode > 299)) {
            throw new ZCHBAPIException('Nalog. ' . $this->_response->statusCode . ' HTTP Error', $url, $post);
        }
        if (is_array($response) === false) {
            throw new ZCHBAPIException('Nalog. Unknown response type', $url, $post);
        }
        if (isset($response['ERRORS']['pbSearchCaptcha']) === true || (isset($response['captchaRequired']) === true && $response['captchaRequired'] === true)) {
            throw new ZCHBAPIException('Nalog. Captcha required', $url, $post);
        }
        return $response;
    }

}