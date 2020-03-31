<?php

namespace frontend\modules\integration\models\evotor;

use common\models\evotor\ReceiptItem as ReceiptItemCommon;
use yii\helpers\ArrayHelper;

class ReceiptItem extends ReceiptItemCommon
{
    use HookTrait;

    public function getGridColumns(): array
    {
        return [
            'name',
            'typeLabel',
            'measure_name',
            'quantity',
            'price',
            'sum_price',
            [
                'attribute' => 'tax',
                'value' => function (self $model) {
                    return $model->tax . ' (' . $model->tax_percent . '%)';
                },
            ],
            'discount',
        ];
    }

    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'typeLabel' => 'Тип',
            'tax' => 'Налог',
            'discount' => 'Скидка',
        ]);
    }

    public function getTypeLabel()
    {
        switch ($this->item_type) {
            case Product::TYPE_NORMAL:
            default:
                return 'обычный';
            case Product::TYPE_ALCOHOL_MARKED:
                return 'алкоголь марк.';
            case Product::TYPE_ALCOHOL_NOT_MARKED:
                return 'алкоголь не марк.';
            case Product::TYPE_SERVICE:
                return 'услуга';
            case Product::TYPE_TOBACCO_MARKED:
                return 'табак марк.';
        }
    }

    /** @inheritDoc */
    protected static function prepareData(array $data): array
    {
        if (array_key_exists('id', $data) === true) {
            $data['uuid'] = $data['id'];
            unset($data['id']);
        }
        if (array_key_exists('itemType', $data) === true) {
            $data['item_type'] = $data['itemType'];
            unset($data['itemType']);
        }
        if (array_key_exists('measureName', $data) === true) {
            $data['measure_name'] = $data['measureName'];
            unset($data['measureName']);
        }
        if (array_key_exists('costPrice', $data) === true) {
            $data['cost_price'] = $data['costPrice'];
            unset($data['costPrice']);
        }
        if (array_key_exists('sumPrice', $data) === true) {
            $data['sum_price'] = $data['sumPrice'];
            unset($data['sumPrice']);
        }
        if (array_key_exists('taxPercent', $data) === true) {
            $data['tax_percent'] = $data['taxPercent'];
            unset($data['taxPercent']);
        }
        if (isset($data['uuid']) === true) {
            $data['product_id'] = Product::idByUUID($data['uuid']);
        }
        return $data;
    }

}