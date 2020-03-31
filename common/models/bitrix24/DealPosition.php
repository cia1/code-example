<?php

namespace common\models\bitrix24;

use yii\db\ActiveRecord;

/**
 * Интеграция Битрикс24: позиции сделки
 *
 * @property int      $id           Идентификатор контакта Битрикс24
 * @property int      $company_id   Идентификатор компании КУБ
 * @property int      $deal_id      Идентификатор сделки
 * @property int|null $product_id   Идентификатор товара
 * @property string   $name         Название позиции
 * @property float    $price        Цена
 * @property bool     $tax_included Включён ли налог в цену
 * @property float    $quantity     Количество
 * @property int      $tax_rate     Ставка НДС
 */
class DealPosition extends ActiveRecord
{

    public static function tableName()
    {
        return 'bitrix24_deal_position';
    }

    public function rules()
    {
        return [
            [['id', 'company_id', 'deal_id', 'name', 'quantity'], 'required'],
            [['id', 'company_id', 'deal_id', 'product_id'], 'integer'],
            ['name', 'string', 'max' => 250],
            [['price', 'quantity'], 'double', 'min' => 0],
            [
                'tax_included',
                'filter',
                'filter' => function ($value) {
                    return $value === '1' || $value === 1 || $value === true || $value === 'Y';
                },
            ],
            ['tax_rate', 'integer', 'max' => 255],
        ];
    }

}