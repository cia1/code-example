<?php

namespace frontend\modules\integration\models\bitrix24;

use common\models\bitrix24\DealPosition as DealPositionCommon;

/**
 * @mixin HookTrait
 */
class DealPosition extends DealPositionCommon
{
    use HookTrait;


    /**
     * В настоящий момент этот метод не используется
     *
     * @param int $id
     * @return null
     */
    public function loadFromRest(/** @noinspection PhpUnusedParameterInspection */ int $id)
    {
        return null;
    }

    public function prepareRestData(array $data)
    {
        //Связь с товаром
        if ($data['product_id']) {
            $model = Product::findOrCreate($this->company_id, $data['product_id'], $this->helper);
            $data['product_id'] = $model->id;
        }
        $data['deal_id'] = $data['owner_id'];
        $data['name'] = $data['product_name'];
        unset($data['owner_id'], $data['product_name']);
        return $data;
    }

    public function getGridColumns(): array
    {
        return [
            'name',
            'price',
            'quantity',
            'discount_sum',
            'tax_rate',
            [
                'attribute' => 'tax_included',
                'value' => function (self $model) {
                    return $model->tax_included ? 'да' : 'нет';
                },
            ],
        ];
    }

    public function attributeLabels()
    {
        return [
            'name' => 'Название',
            'price' => 'Цена',
            'quantity' => 'Количество',
            'discount_sum' => 'Скидка',
            'tax_rate' => 'Налог',
            'tax_included' => 'НДС включён',
        ];
    }

}