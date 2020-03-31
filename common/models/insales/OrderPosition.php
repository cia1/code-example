<?php

namespace common\models\insales;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * Интеграция InSales CMS: товарные позиции заказа
 *
 * @property int         $id                Идентфикатор товарной позиции в InSales
 * @property int         $order_id          Идентификатор заказа
 * @property int         $product_id        Идентификатор товара
 * @property float       $full_sale_price   Цена продажи
 * @property float       $discount_amount   Сумма скидки
 * @property int         $quantity          Количество
 * @property int         $reserved_quantity Зарезервированное количество
 * @property float       $weight            Вес
 * @property string|null $comment
 *
 * @property Product     $product
 *
 */
class OrderPosition extends ActiveRecord
{

    public static function tableName()
    {
        return 'insales_order_position';
    }

    public function rules()
    {
        return [
            [['order_id', 'product_id', 'full_sale_price', 'quantity'], 'required'],
            [['order_id', 'product_id', 'quantity', 'reserved_quantity'], 'integer'],
            [['full_sale_price', 'discount_amount', 'weight'], 'double'],
            ['comment', 'string', 'max' => 500],
        ];
    }

    public function getProduct(): ActiveQuery
    {
        return $this->hasOne(Product::class, ['id' => 'product_id']);
    }

}