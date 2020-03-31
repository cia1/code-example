<?php

namespace common\models\bitrix24;

use common\components\helpers\ArrayHelper;
use common\components\IntegrationDateBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * Интеграция Битрикс24: товары
 *
 * @property int         $id           Идентификатор товара Битрикс24
 * @property int         $company_id   Идентификатор компании КУБ
 * @property int         $section_id   Идентификатор группы (категории)
 * @property int|null    vat_id        Идентификатор налоговой группы
 * @property bool        $status
 * @property string      $name         Название товара
 * @property string|null $picture      URL изображения товара
 * @property int         $sort
 * @property int         $created_at   UNIXTIME создания товара
 * @property float       $price        Цена
 * @property string      $description
 * @property string      $currency     Код валюты
 * @property bool        $vat_included Включён ли налог в цену
 *
 * @property Vat         $vat          Налоговая группа
 */
class Product extends ActiveRecord
{

    public static function tableName()
    {
        return 'bitrix24_product';
    }

    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            [
                'class' => IntegrationDateBehavior::class,
                'columns' => ['created_at'],
            ],
        ]);
    }

    public function rules()
    {
        return [
            ['name', 'required'],
            [['company_id', 'section_id', 'vat_id', 'sort'], 'integer'],
            [
                ['status', 'vat_included'],
                'filter',
                'filter' => function ($value) {
                    return $value === '1' || $value === 1 || $value === true || $value === 'Y';
                },
            ],
            ['name', 'string'],
            ['picture', 'string', 'max' => 1000],
            ['price', 'double', 'min' => 0],
            ['currency', 'string', 'length' => 3],
        ];
    }

    public function getVat(): ActiveQuery
    {
        return $this->hasOne(Vat::class, ['company_id' => 'company_id', 'id' => 'vat_id']);
    }
}