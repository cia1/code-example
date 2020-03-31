<?php

namespace common\models\bitrix24;

use common\components\helpers\ArrayHelper;
use common\components\IntegrationDateBehavior;
use common\models\document\query\ActQuery;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * Интеграция Битрикс24: счёт
 *
 * @property int          $id                  Идентификатор счёта Битрикс24
 * @property int          $company_id          Идентификатор клиента КУБ
 * @property int|null     $deal_id             Идентификатор сделки
 * @property int|null     $bitrix24_company_id Идентификатор компании
 * @property int|null     $contact_id          Идентификатор контакта
 * @property string       $number              Номер счёта
 * @property string       $status              Статус
 * @property float        $amount              Сумма
 * @property string       $currency            Валюта
 * @property float        $tax                 Сумма налога
 * @property string|null  $comment             Комментарий
 * @property string|null  $comment_manager     Комментарий менеджера
 * @property int|null     $date_paid           Дата оплаты
 * @property int          $created_at          Дата и время создания счёта
 * @property int          $updated_at          Дата и время последнего изменения счёта
 *
 * @property Deal|null    $deal
 * @property Company|null $company
 * @property Contact|null $contact
 *
 */
class Invoice extends ActiveRecord
{

    public static function tableName()
    {
        return 'bitrix24_invoice';
    }

    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            [
                'class' => IntegrationDateBehavior::class,
                'columns' => [
                    'created_at',
                    'updated_at',
                    'date_paid' => false,
                ],
            ],
        ]);
    }

    public function rules()
    {
        return [
            [['id', 'company_id', 'number', 'status'], 'required'],
            [['company_id', 'deal_id', 'bitrix24_company_id', 'contact_id'], 'integer'],
            ['number', 'string', 'max' => 30],
            ['status', 'string', 'max' => 50],
            [['amount', 'tax'], 'double', 'min' => 0],
            ['currency', 'string', 'length' => 3],
            [['comment', 'comment_manager'], 'string'],
        ];
    }

    public function getDeal(): ActiveQuery
    {
        return $this->hasOne(Deal::class, ['company_id' => 'company_id', 'id' => 'deal_id']);
    }

    public function getCompany(): ActiveQuery
    {
        return $this->hasOne(Company::class, ['company_id' => 'company_id', 'id' => 'bitrix24_company_id']);
    }

    public function getContact(): ActiveQuery
    {
        return $this->hasOne(Contact::class, ['company_id' => 'company_id', 'id' => 'contact_id']);
    }
}