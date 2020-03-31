<?php

namespace frontend\modules\integration\helpers;

use Facebook\Facebook;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;
use FacebookAds\Object\AdAccount;
use FacebookAds\Api;

/**
 * Помощник загрузки данных из Facebook
 */
class FacebokHelper
{

    const FACEBOOK_APP_ID = '426911014625985'; //ID приложения
    const FACEBOOK_SECRET = 'a11ffb966b8106bb6667622f3cd80297'; //Секретный ключ приложения

    private $_fbUserId;
    private $_fbToken;
    private $_graphUser;

    /**
     * @param string $fbUserId Идентификатор пользователя
     * @param string $fbToken  Токен авторизации
     */
    public function __construct(string $fbUserId, string $fbToken)
    {
        $this->_fbUserId = $fbUserId;
        $this->_fbToken = $fbToken;
        Api::init(self::FACEBOOK_APP_ID, self::FACEBOOK_SECRET, $fbToken);
    }

    /**
     * Возвращает имя и идентификатор "пользователя" файсбук
     *
     * @return array
     * @throws FacebookResponseException (на самом деле может быть выброшено)
     * @throws FacebookSDKException
     */
    public function graphUser(): array
    {
        if ($this->_graphUser === null) {
            $fb = new Facebook([
                'app_id' => self::FACEBOOK_APP_ID,
                'app_secret' => self::FACEBOOK_SECRET,
                'default_graph_version' => 'v5.0',
            ]);
            $response = $fb->get('/' . $this->_fbUserId . '?fields=name,personal_ad_accounts', $this->_fbToken);
            $result = $response->getGraphUser()->asArray();
            $id = $result['personal_ad_accounts'][0]['id'] ?? null;
            if ($id === null) {
                throw new FacebookSDKException('Unknown AD account');
            }
            $this->_graphUser = [
                'name' => $result['name'],
                'adAccountId' => $id,
            ];
        }
        return $this->_graphUser;
    }

    /**
     * Данные по рекламным компаниям
     *
     * @param string $dateFrom Начало периода (ГГГГ-ММ-ДД)
     * @param string $dateTo   Конец периода (ГГГГ-ММ-ДД)
     * @return array
     * @throws FacebookResponseException
     * @throws FacebookSDKException
     */
    public function getData(string $dateFrom, string $dateTo): array
    {
        $fields = [
            'account_name',
            'campaign_name',
            'cpp',
            'cpm',
            'clicks',
            'ad_name',
            'spend',
            'account_currency',
            'reach',
            'actions',
            'video_play_actions',
            'video_avg_time_watched_actions',
            'outbound_clicks',
            'impressions',
        ];
        $params = [
            'level' => 'campaign',
            'filtering' => [],
            'breakdowns' => [],
            'time_range' => ['since' => $dateFrom, 'until' => $dateTo],
        ];
        return ((new AdAccount($this->graphUser()['adAccountId']))->getInsights(
                $fields,
                $params
            )->getLastResponse()->getContent())['data'] ?? [];
    }
}