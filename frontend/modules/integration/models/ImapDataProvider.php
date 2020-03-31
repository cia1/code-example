<?php

namespace frontend\modules\integration\models;

use frontend\modules\integration\helpers\ImapHelper;
use yii\base\InvalidConfigException;
use yii\data\ArrayDataProvider;
use unyii2\imap\Exception;

class ImapDataProvider extends ArrayDataProvider
{

    const PAGINATION_DEFAULT_PER_PAGE = 20;

    /** @var ImapHelper */
    private $_helper;

    public $allModels = [];

    /**
     * @param ImapHelper $helper
     */
    public function __construct(ImapHelper $helper)
    {
        $this->_helper = $helper;
        parent::__construct([
            'pagination' => [
                'defaultPageSize' => self::PAGINATION_DEFAULT_PER_PAGE,
                'page' => isset($_GET['page']) && $_GET['page'] > 0 ? $_GET['page'] - 1 : 0,
            ],
            'sort' => [
                'defaultOrder' => 'date DESC',
                'attributes' => ['date'],
            ],
        ]);
    }


    protected function sortModels($models, $sort)
    {
        $orders = $this->getSort()->orders;
        if (isset($orders['date']) === true && $orders['date'] == 4) {
            return array_reverse($models);
        } else {
            return $models;
        }
    }

    /**
     * @param bool $forcePrepare
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function prepare($forcePrepare = false)
    {
        $this->allModels = $this->_helper->getMailListCache($this->pagination->getOffset(), $this->pagination->getLimit());
        parent::prepare($forcePrepare);
    }
}