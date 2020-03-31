<?php

namespace common\models\insales;

use common\components\helpers\ArrayHelper;
use common\components\IntegrationDateBehavior;
use yii\db\ActiveRecord;

/**
 * Интеграция InSales CMS: товары
 *
 * @property int         $id          Идентификатор товара в InSales
 * @property int         $company_id  Идентификатор компании КУБ
 * @property float       $sale_price  Цена
 * @property string|null $sku         Артикул
 * @property string      $title       Название
 * @property int|null    $vat
 * @property string|null $barcode     Штрихкод
 * @property string|null $unit        Единица измерения
 * @property int         $created_at
 * @property int         $updated_at
 */
class Product extends ActiveRecord
{

    public static function tableName()
    {
        return 'insales_product';
    }

    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            IntegrationDateBehavior::class,
        ]);
    }

    public function rules()
    {
        return [
            [['company_id', 'sale_price', 'title'], 'required'],
            [
                'vat',
                'filter',
                'filter' => function ($value) {
                    if ($value == -1) {
                        return null;
                    }
                    return (int)$value;
                },
            ],
            [['company_id', 'vat'], 'integer'],
            ['sale_price', 'double'],
            ['sku', 'string', 'max' => 30],
            ['title', 'string', 'max' => 100],
            ['barcode', 'string', 'max' => 40],
            ['unit', 'string', 'max' => 20],
        ];
    }
}