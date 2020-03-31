<?php

namespace common\components\zchb;

use Exception;
use Throwable;
use Yii;

/**
 * Исключение, выбрасываемое при ошибках API.
 *
 * @see https://zachestnyibiznesapi.ru/#status-list
 * @param string $message Описание ошибки, полученное от API
 * @param string $method  Запрошенный API-метод
 * @param string $params  URI-строка параметров
 *
 */
class ZCHBAPIException extends Exception
{
    const LOG_FILE_NAME = 'zchb.log';

    public function __construct($message = "", string $method = null, $params = null)
    {
        parent::__construct($message);
        $this->log($method, $params);
    }

    protected function log($method, $params)
    {
        if ($method === null) {
            return;
        }
        if (is_array($params) === true) {
            $params = http_build_query($params);
        }
        $s = date('d.m.Y H:i:s') . "\t" . $this->getMessage() . "\t" . ($method ? $method : '') . ($params ? ' ?' . $params : '') . "\n";
        file_put_contents(Yii::getAlias('@runtime/logs/' . self::LOG_FILE_NAME), $s, FILE_APPEND);
    }

}