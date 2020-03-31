<?php

namespace frontend\modules\integration\helpers;

use Exception;
use Yii;

/**
 * Помощник обмена данными ВКонтакте
 */
class VkHelper
{

    const CLIENT_ID = 7153606; //ID приложения
    const SECRET = 'Oo3eqG3sxb45qvrbUFlm'; //Секретный ключ приложения

    /**
     * Возвращает ссылку для выполнения редиректа для интерактивной авторизации на сервере ВКонтакте (OAuth v2)
     *
     * @return string
     */
    public static function getAuthLink()
    {
        $link = 'https://' . $_SERVER['HTTP_HOST'] . '/integration/vk';
        $link = 'https://oauth.vk.com/authorize?client_id=' . static::CLIENT_ID . '&display=page&redirect_uri=' . urlencode($link) . '&scope=ads&response_type=code&v=5.101';
        return $link;
    }

    /**
     * Возвращает ключ доступа
     *
     * @param string      $code         Код авторизации, полученный после авторизации. @see self::getAuthLink()
     * @param string|null $errorMessage Через эту ссылку передаётся сообщение об ошибке, полученное от сервера ВКонтакте
     * @return string|null
     */
    public static function getToken(string $code, &$errorMessage)
    {
        $link = 'https://' . $_SERVER['HTTP_HOST'] . '/integration/vk';
        $link = 'https://oauth.vk.com/access_token?client_id=' . static::CLIENT_ID . '&client_secret=' . static::SECRET . '&code=' . $code . '&redirect_uri=' . urlencode($link);
        try {
            $response = @json_decode(file_get_contents($link), true);
        } catch (Exception $e) {
            $response = [];
        }
        $token = $response['access_token'] ?? null;
        if ($token === null) {
            if (isset($response['error_description']) === true) {
                $errorMessage = $response['error_description'];
            } else {
                $errorMessage = null;
            }
            return null;
        }
        return $token;
    }

    private $_data;

    public function __construct(string $token, int $accountId, string $dateFrom, string $dateTo)
    {
        $this->load($token, $accountId, $dateFrom, $dateTo);
    }

    /**
     * Возвращает информацию по рекламной компании
     *
     * @param int $id
     * @return array|null
     */
    public function companyInfo(int $id)
    {
        return $this->_data['ads'][$id] ?? null;
    }

    /**
     * Фильтрует данные по рекламной компании
     *
     * @param int $id Идентификатор компании
     */
    public function filterByCompany(int $id)
    {
        foreach ($this->_data['stat'] as $i => $item) {
            if ($item['id'] != $id) {
                unset($this->_data['stat'][$i]);
            }
        }
    }

    /**
     * Группирует информацию по компаниям
     *
     * @return array
     */
    public function groupByCompany(): array
    {
        $out = [];
        foreach ($this->_data['stat'] as $item) {
            $id = $item['id'];
            $data = $this->_data['ads'][$id];
            $data['id'] = $id;
            $data['spent'] = 0;
            $data['impressions'] = 0;
            $data['clicks'] = 0;
            $data['reach'] = 0;
            foreach ($item['stats'] as $stat) {
                $data['spent'] += ($stat['spent'] ?? 0);
                $data['impressions'] += ($stat['impressions'] ?? 0);
                $data['clicks'] += ($stat['clicks'] ?? 0);
                $data['reach'] += ($stat['reach'] ?? 0);
            }
            $out[] = $data;
        }
        return $out;
    }

    /**
     * Группирует информацию по дням
     *
     * @return array
     */
    public function groupByDays(): array
    {
        $out = [];
        foreach ($this->_data['stat'] as $item) {
            foreach ($item['stats'] as $stat) {
                $day = $stat['day'];
                if (isset($out[$day]) === false) {
                    $out[$day] = [
                        'date' => $day,
                        'spent' => 0,
                        'impressions' => 0,
                        'clicks' => 0,
                        'reach' => 0,
                    ];
                }
                $out[$day]['spent'] += ($stat['spent'] ?? 0);
                $out[$day]['impressions'] += ($stat['impressions'] ?? 0);
                $out[$day]['clicks'] += ($stat['clicks'] ?? 0);
                $out[$day]['reach'] += ($stat['reach'] ?? 0);
            }
        }
        return array_values($out);
    }

    protected function load(string $token, int $accountId, string $dateFrom, string $dateTo)
    {
        $hash = md5($token . $accountId . $dateFrom . $dateTo);
        $session = Yii::$app->session;
        if ($session->get('vkAdsHash') !== $hash) {
            $session->remove('vkAdsData');
            $session->set('vkAdsHash', $hash);
        }
        $data = $session->get('vkAdsData');
        if ($data === null) {
            $data = self::_loadFromVk($token, $accountId, $dateFrom, $dateTo);
            $session->set('vkAdsData', $data);
        }
        $this->_data = $data;
    }

    /**
     * Возвращает информацию по рекламным компаниям: 'ads': рекламные компании, 'stat': статистика по дням
     *
     * @param int    $accountId Номер аккаунта
     * @param string $token     Ключ доступа
     * @param string $dateFrom  Начало периода (ГГГГ-ММ-ДД)
     * @param string $dateTo    Конец периода (ГГГГ-ММ-ДД)
     * @return array
     */
    private static function _loadFromVk(string $token, int $accountId, string $dateFrom, string $dateTo): array
    {
        $link = 'https://api.vk.com/method/ads.getAds?account_id=' . $accountId . '&client_id=' . static::CLIENT_ID . '&access_token=' . $token . '&v=5.101';
        $tmp = json_decode(file_get_contents($link), true)['response'] ?? [];
        $ads = [];
        foreach ($tmp as $item) {
            $id = $item['id'];
            unset($item['id']);
            $ads[$id] = $item;
        }
        unset($tmp);
        if (count($ads) > 0) {
            $link = 'https://api.vk.com/method/ads.getStatistics?account_id=' . $accountId . '&ids_type=ad&ids=' . implode(',',
                    array_keys($ads)) . '&period=day&date_from=' . $dateFrom . '&date_to=' . $dateTo . '&access_token=' . $token . '&v=5.101';
            $stat = json_decode(file_get_contents($link), true)['response'] ?? [];
        } else {
            $stat = [];
        }
        return ['ads' => $ads, 'stat' => $stat];
    }
}