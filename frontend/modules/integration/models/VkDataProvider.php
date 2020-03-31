<?php

namespace frontend\modules\integration\models;

use common\components\helpers\Html;
use frontend\modules\integration\helpers\VkHelper;
use yii\data\ArrayDataProvider;
use yii\helpers\Url;

use Yii;

/**
 * Провайдер данных по рекламным компаниям ВКонтакте
 *
 * @see VkHelper::getAdsData()
 */
class VkDataProvider extends ArrayDataProvider
{

    const PAGINATION_DEFAULT_PER_PAGE = 20;

    public $allModels = [];

    public function __construct(array $allModels)
    {
        $this->allModels = $allModels;
        $pagination = [
            'defaultPageSize' => self::PAGINATION_DEFAULT_PER_PAGE,
            'page' => isset($_GET['page']) && $_GET['page'] > 0 ? $_GET['page'] - 1 : 0,
        ];
        if (Yii::$app->request->get('per-page') === '0') {
            $pagination['pageSize'] = 5000;
        }
        parent::__construct([
            'pagination' => $pagination,
        ]);
    }

    /**
     * Возвращает массив столбцов для Grid'а
     *
     * @return array
     */
    public function columns(): array
    {
        $this->prepare();
        if (count($this->allModels) < 1) {
            return [];
        }
        $item = array_keys($this->allModels[0]);
        return array_intersect_key(self::allColumns(), array_combine($item, $item));
    }

    /**
     * Определяет конфигурацию всех возможных полей для Grid::widget(), в таблицу попадают не все поля
     *
     * @return array
     * @see self::columns()
     */
    protected static function allColumns(): array
    {
        return [
            'name' => [
                'attribute' => 'name',
                'label' => 'Компания',
                'value' => function ($item) {
                    return Html::a($item['name'], Url::to('/integration/vk/' . $item['id']));
                },
                'format' => 'raw',
            ],
            'date' => [
                'attribute' => 'date',
                'label' => 'Дата',
            ],
            'day_limit' => [
                'attribute' => 'day_limit',
                'label' => 'Лимит (день/всего)',
                'value' => function ($item) {
                    if ($item['day_limit'] == 0 && $item['all_limit'] == 0) {
                        return '';
                    }
                    return $item['day_limit'] . ' / ' . $item['all_limit'];
                },
            ],
            'cpc' => [
                'attribute' => 'cpc',
                'label' => 'CPC',
            ],
            'spent' => [
                'attribute' => 'spent',
                'label' => 'Расходы',
            ],
            'impressions' => 'impressions',
            'clicks' => [
                'attribute' => 'clicks',
                'label' => 'Клики',
            ],
            'reach' => 'reach',
        ];
    }

    /**
     * Возвращает массив ключ-значение, состоящий из суммарных значений всех атрибутов, указанных в $attribute
     *
     * @param array $attribute Список атрибутов
     * @return array
     */
    public function calculateTotal(array $attribute): array
    {
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