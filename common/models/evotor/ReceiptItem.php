<?php

namespace common\models\evotor;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * Интеграция Эвотор: позиции чека
 *
 * @property int      $id
 * @property int      $receipt_id   ID чека (evotor_receipt.id)
 * @property int|null $product_id   ID товара (evotor_product.id)
 * @property string   $item_type    Тип товара, @see Product::TYPE
 * @property string   $name         Название товара
 * @property string   $measure_name Единица измерения
 * @property int      $quantity     Количество
 * @property float    $price        Отпускная цена
 * @property float    $cost_price   Закупочная цена
 * @property float    $sum_price    Итоговая сумма позиции
 * @property float    $tax          Сумма налога
 * @property float    $tax_percent  Сумма налога, выраженная в процентах
 * @property float    $discount     Сумма скидки
 * @property string   $uuid         UUID товара (не обязан совпрадать с Product.uuid)
 *
 * @property Product  $product
 * @property Receipt  $receipt
 */
class ReceiptItem extends ActiveRecord
{

    public static function tableName()
    {
        return 'evotor_receipt_item';
    }

    public function rules()
    {
        return [
            [['receipt_id', 'name'], 'required'],
            ['name', 'string', 'max' => 100],
            ['uuid', 'string', 'length' => 36],
            [['receipt_id', 'product_id'], 'integer'],
            ['item_type', 'in', 'range' => Product::TYPE],
            ['measure_name', 'string', 'max' => 10],
            ['quantity', 'integer'],
            [['price', 'cost_price', 'sum_price', 'tax', 'tax_percent', 'discount'], 'double', 'min' => 0],
        ];
    }

    public function getProduct(): ActiveQuery
    {
        return $this->hasOne(Product::class, ['id' => 'product_id']);
    }

    public function getReceipt(): ActiveQuery
    {
        return $this->hasOne(Receipt::class, ['id' => 'receipt_id']);
    }

    public function attributeLabels()
    {
        return [
            'name' => 'Название',
            'quantity' => 'Количество',
            'measure_name' => 'Ед.',
            'price' => 'Цена',
            'sum_price' => 'Сумма',
            'tax_percent' => 'НДС',
        ];
    }
}