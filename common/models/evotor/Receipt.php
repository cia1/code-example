<?php

namespace common\models\evotor;

use common\components\helpers\ArrayHelper;
use common\components\IntegrationDateBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * Интеграция Эвотор: чеки
 *
 * @see https://api.evotor.ru/docs/#tag/Vebhuki-uvedomleniya%2Fpaths%2F~1partner.ru~1api~1v2~1receipts%2Fpost
 *
 * @property string        $id
 * @property int           $company_id         ID компании КУБ
 * @property int           $device_id          ID терминала (evotor_device.id)
 * @property int           $store_id           ID магазина (evotor_store.id)
 * @property string        $uuid               UUID чека Эвотор (внешний ID)
 * @property int           $date_time          Дата и время операции
 * @property string        $type               Тип документа
 * @property string        $evotor_employee_id ID клиента в Эвотор
 * @property float         $total_tax          Сумма налога
 * @property float         $total_discount     Сумма скидки
 * @property float         $total_amount       Сумма по документу
 * @property bool          $cash               Наличная оплата
 * @property int           $itemsCount         Количество позиций в чеке
 * @property Device        $device
 * @property Store         $store
 * @property Employee      $evotorEmployee
 * @property ReceiptItem[] $receiptItems
 *
 */
class Receipt extends ActiveRecord
{
    //Типы документа
    const TYPE_SELL = 'SELL';
    const TYPE_PAYBACK = 'PAYBACK';
    const TYPE = [
        self::TYPE_SELL,
        self::TYPE_PAYBACK,
    ];

    /**
     * Тип платежа. В документации ничего не сказано, но скорее всего других типов нет.
     */
    const PAYMENT_TYPE_CARD = 'PAY_CARD';

    public static function tableName()
    {
        return 'evotor_receipt';
    }

    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            [
                'class' => IntegrationDateBehavior::class,
                'columns' => ['date_time'],
            ],
        ]);
    }

    public function rules()
    {
        return [
            [['company_id', 'device_id', 'store_id', 'evotor_employee_id'], 'required'],
            [['company_id', 'device_id', 'store_id', 'evotor_employee_id'], 'integer'],
            ['uuid', 'string', 'length' => 36],
            ['cash', 'boolean'],
            ['type', 'in', 'range' => static::TYPE],
            [['total_tax', 'total_discount', 'total_amount'], 'double'],
            ['cash', 'boolean'],
        ];
    }

    public function getItemsCount(): int
    {
        return $this->getReceiptItems()->count();
    }

    public function getDevice(): ActiveQuery
    {
        return $this->hasOne(Device::class, ['id' => 'device_id']);
    }

    public function getStore(): ActiveQuery
    {
        return $this->hasOne(Store::class, ['id' => 'store_id']);
    }

    public function getEvotorEmployee(): ActiveQuery
    {
        return $this->hasOne(Employee::class, ['id' => 'evotor_employee_id']);
    }

    public function getReceiptItems(): ActiveQuery
    {
        return $this->hasMany(ReceiptItem::class, ['receipt_id' => 'id']);
    }

}