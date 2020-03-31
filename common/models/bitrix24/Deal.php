<?php

namespace common\models\bitrix24;

use common\components\helpers\ArrayHelper;
use common\components\IntegrationDateBehavior;
use common\models\document\query\ActQuery;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * Интеграция Битрикс24: сделка
 *
 * @property int            $id                  Идентификатор контакта Битрикс24
 * @property int            $company_id          Идентификатор клиента КУБ
 * @property int|null       $bitrix24_company_id Идентификатор компании
 * @property int|null       $contact_id          Идентификатор контакта
 * @property string         $title               Название сделки
 * @property string         $type                Тип сделки
 * @property string         $stage               Этап
 * @property string         $currency            Валюта
 * @property float          $amount              Сумма сделки
 * @property float          $tax                 Сумма налога
 * @property string         $status              Статус @see self::STATUS
 * @property string         $source              Источник сделки
 * @property string|null    $source_description  Комментарий к источнику
 * @property int|null       $begin_date          Дата открытия
 * @property int|null       $close_date          Дата закрытия
 * @property int            $created_at          Дата и время добавления сделки
 * @property int            $updated_at          Дата и время последнего измененеия сделки
 *
 * @property Company|null   $company
 * @property Contact|null   $contact
 * @property DealPosition[] $positions
 * @property int            $positionCount       Количество позиций в сделке
 * @property Invoice[]      $invoices
 * @property int            $invoiceCount        Количество счетов по этой сделке
 *
 */
class Deal extends ActiveRecord
{

    //Состояние сделки
    const STATUS_OPEN = 'OPEN'; //сделка открыта
    const STATUS_CLOSE = 'CLOSE'; //сделка закрыта
    const STATUS = [
        self::STATUS_OPEN,
        self::STATUS_CLOSE,
    ];

    public static function tableName()
    {
        return 'bitrix24_deal';
    }

    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            [
                'class' => IntegrationDateBehavior::class,
                'columns' => [
                    'created_at',
                    'updated_at',
                    'begin_date' => false,
                    'close_date' => false,
                ],
            ],
        ]);
    }

    public function rules()
    {
        return [
            [['id', 'company_id', 'title', 'stage'], 'required'],
            [['id', 'company_id', 'bitrix24_company_id', 'contact_id'], 'integer'],
            ['title', 'string', 'max' => 120],
            [['type', 'stage', 'source'], 'string', 'max' => 50],
            ['currency', 'string', 'length' => 3],
            [['amount', 'tax'], 'double', 'min' => 0],
            ['status', 'in', 'range' => self::STATUS],
            ['source_description', 'string'],
        ];
    }

    public function getCompany(): ActiveQuery
    {
        return $this->hasOne(Company::class, ['company_id' => 'company_id', 'id' => 'bitrix24_company_id']);
    }

    public function getContact(): ActiveQuery
    {
        return $this->hasOne(Contact::class, ['company_id' => 'company_id', 'id' => 'contact_id']);
    }


    public function getPositions(): ActiveQuery
    {
        return $this->hasMany(DealPosition::class, ['company_id' => 'company_id', 'deal_id' => 'id']);
    }


    public function getPositionCount(): int
    {
        return $this->getPositions()->count();
    }

    public function getInvoices(): ActiveQuery
    {
        return $this->hasMany(Invoice::class, ['company_id' => 'company_id', 'deal_id' => 'id']);
    }

    public function getInvoiceCount(): int
    {
        return $this->getInvoices()->count();
    }

}