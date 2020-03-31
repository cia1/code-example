<?php

namespace common\models\bitrix24;

use yii\db\ActiveRecord;

/**
 * Интеграция Битрикс24: товарные каталоги
 *
 * @property int    $id            Идентификатор каталога Битрикс24
 * @property int    $company_id    Идентификатор компании КУБ
 * @property string $name          Название каталога
 */
class Catalog extends ActiveRecord
{

    public static function tableName()
    {
        return 'bitrix24_catalog';
    }

    public function rules()
    {
        return [
            [['company_id', 'name'], 'required'],
            ['company_id', 'integer'],
            ['name', 'string', 'max' => 120],
        ];
    }

}