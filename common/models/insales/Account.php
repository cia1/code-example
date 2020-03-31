<?php

namespace common\models\insales;

use kdn\yii2\validators\DomainValidator;
use yii\db\ActiveRecord;

/**
 * Интеграция InSales: магазины CMS
 * Таблица используется для связи клиентов сайта с клиентами InSales при обращении к REST API.
 *
 * @property int    $id               Идентификатор магазина
 * @property int    $company_id       Идентификатор компании КУБ
 * @property string $user_id          Идентификатор пользователя InSales
 * @property string $shop             Домен магазина
 * @property string $password         Пароль доступа к API
 */
class Account extends ActiveRecord
{

    public static function tableName()
    {
        return 'insales_account';
    }

    public function rules()
    {
        return [
            [['id', 'shop', 'password'], 'required'],
            [['id', 'company_id', 'user_id'], 'integer'],
            ['shop', DomainValidator::class],
            ['password', 'string', 'length' => 32],
        ];
    }

}