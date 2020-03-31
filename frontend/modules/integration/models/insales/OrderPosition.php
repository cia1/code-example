<?php

namespace frontend\modules\integration\models\insales;

use common\models\insales\OrderPosition as OrderPositionCommon;

class OrderPosition extends OrderPositionCommon
{

    /**
     * Создаёт или обновляет товарную позицию заказа
     *
     * @param int   $id        Идентификатор позиции в InSales
     * @param int   $orderId   Идентификатор заказа
     * @param int   $productId Идентификатор товара
     * @param array $data      Данные товарной позиции
     * @return self|null
     */
    public static function createOrUpdate(int $id, int $orderId, int $productId, array $data)
    {
        $position = self::findOne(['id' => $id]);
        if ($position === null) {
            $position = new self();
            $position->id = $id;
            $position->order_id = $orderId;
        }
        $position->load($data, '');
        $position->product_id = $productId;
        if ($position->save() === false) {
            return null;
        }
        return $position;
    }

    public function getGridColumns(): array
    {
        return [
            'product.title',
            'quantity',
            'reserved_quantity',
            'full_sale_price',
            'discount_amount',
            'weight',
            'comment',
        ];
    }

    public function attributeLabels()
    {
        return [
            'product.title' => 'Товар',
            'quantity' => 'Количество',
            'reserved_quantity' => 'Резерв',
            'full_sale_price' => 'Цена',
            'discount_amount' => 'Скидка',
            'weight' => 'Вес',
            'comment' => 'Комментарий',
        ];
    }

}

