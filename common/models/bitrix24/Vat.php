<?php

namespace common\models\bitrix24;

use yii\db\ActiveRecord;

/**
 * Интеграция Битрикс24: налоговые группы
 *
 * @property int    $id         Идентификатор налоговой группы Битрикс24
 * @property int    $company_id Идентификатор компании КУБ
 * @property string $name       Название группы
 * @property float  $rate       Налоговая ставка
 */
class Vat extends ActiveRecord
{

    public static function tableName()
    {
        return 'bitrix24_vat';
    }

    public function rules()
    {
        return [
            [['name', 'rate'], 'required'],
            ['name', 'string', 'max' => 20],
            ['rate', 'double'],

        ];
    }

}