<?php

namespace frontend\modules\integration\models;

use yii\data\ArrayDataProvider;
use frontend\modules\integration\helpers\FacebokHelper;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;

/**
 * Провайдер данных по рекламным компаниям Facebook
 *
 * @see FacebokHelper::getData()
 */
class FacebookDataProvider extends ArrayDataProvider
{

    const PAGINATION_DEFAULT_PER_PAGE = 20;

    public $allModels = [];

    /** @var FacebokHelper */
    private $_helper;
    private $_dateFrom;
    private $_dateTo;

    /**
     * @param FacebokHelper $helper
     * @param string        $dateFrom (ГГГГ-ММ-ДД)
     * @param string        $dateTo   (ГГГГ-ММ-ДД)
     */
    public function __construct(FacebokHelper $helper, string $dateFrom, string $dateTo)
    {
        $this->_helper = $helper;
        $this->_dateFrom = $dateFrom;
        $this->_dateTo = $dateTo;

        parent::__construct([
            'pagination' => [
                'defaultPageSize' => self::PAGINATION_DEFAULT_PER_PAGE,
                'page' => isset($_GET['page']) && $_GET['page'] > 0 ? $_GET['page'] - 1 : 0,
            ],
        ]);
    }

    /**
     * @param bool $forcePrepare
     * @throws FacebookResponseException
     * @throws FacebookSDKException
     */
    public function prepare($forcePrepare = false)
    {
        if (!$this->allModels) {
            $this->allModels = $this->_helper->getData($this->_dateFrom, $this->_dateTo);
        }
        parent::prepare($forcePrepare);
    }

    /**
     * @param array $attribute
     * @return array
     * @throws FacebookResponseException
     * @throws FacebookSDKException
     */
    public function calculateTotal(array $attribute): array
    {
        $this->prepare();
        $total = [];
        array_map(function ($a) use (&$total) {
            $total[$a] = 0;
        }, $attribute);
        foreach ($this->allModels as $item) {
            foreach ($attribute as $at) {
                $total[$at] += ($item[$at] ?? 0);
            }
        }
        return $total;
    }
}