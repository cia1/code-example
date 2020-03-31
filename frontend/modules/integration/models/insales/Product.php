<?php

namespace frontend\modules\integration\models\insales;

use common\models\insales\Product as ProductCommon;

class Product extends ProductCommon
{

    /**
     * Создаёт или обновляет данные товара
     *
     * @param int   $id        Идентификатор товара InSales
     * @param int   $companyId Идентификатор клиента КУБ
     * @param array $data      Данные клиента
     * @return self|null
     */
    public static function createOrUpdate(int $id, int $companyId, array $data)
    {
        $product = self::findOne(['id' => $id]);
        if ($product === null) {
            $product = new self();
            $product->id = $id;
            $product->company_id = $companyId;
        }
        $product->load($data, '');
        if ($product->save() === false) {
            return null;
        }
        return $product;
    }

    public function getGridColumns(): array
    {
        return [
            'title',
            'sale_price',
            'sku',
            'barcode',
            'unit',
            [
                'attribute' => 'vat',
                'value' => function (self $model) {
                    return $model->vat ? $model->vat . '%' : null;
                },
            ],
        ];
    }

    public function attributeLabels()
    {
        return [
            'title' => 'Название',
            'sku' => 'Артикул',
            'barcode' => 'Штрихкод',
            'unit' => 'Ед. измерения',
            'vat' => 'НДС',
        ];
    }

}