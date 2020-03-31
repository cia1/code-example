<?php

namespace common\models\evotor;

use common\components\helpers\ArrayHelper;
use common\components\IntegrationDateBehavior;
use yii\db\ActiveRecord;

/**
 * Интегарция Эвотор: магазины
 *
 * @see https://developer.evotor.ru/docs/rest_stores.html
 *
 * @property string $id
 * @property int    $company_id   Идентификатор компании КУБ
 * @property string $uuid         UUID магазина в Эвотор (внешний ID)
 * @property string $name         Название магазина
 * @property string $address      Адрес магазина
 * @property int    $created_at   UNIXTIME создания примечания
 * @property int    $updated_ay   UNIXTIME последнего изменения примечания
 */
class Store extends ActiveRecord
{

    public static function tableName()
    {
        return 'evotor_store';
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
            [['company_id', 'name'], 'required'],
            ['company_id', 'integer'],
            ['uuid', 'string', 'length' => 36],
            [['name', 'address'], 'string', 'max' => 400],
        ];
    }

    public function attributeLabels()
    {
        return [
            'name' => 'Название',
            'address' => 'Адрес',
            'created_at' => 'Дата создания',
            'updated_at' => 'Дата изменения',
        ];
    }

}