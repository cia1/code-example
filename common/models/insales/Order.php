<?php

namespace common\models\insales;

use common\components\helpers\ArrayHelper;
use common\components\IntegrationDateBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * Интеграция InSales CMS: заказы
 *
 * @property int             $id                   Идентификатор заказа InSales
 * @property int             $company_id           Идентификатор компании КУБ
 * @property int             $client_id            Идентификатор клиента InSales
 * @property int|null        $shipping_address_id  Идентификатор адреса доставки InSales
 * @property string          $number               Номер заказа
 * @property string          $key
 * @property float           $total_price          Итоговая сумма заказа
 * @property float           $items_price          Итоговая сумма товаров
 * @property string          $currency_code        Валюта
 * @property boolean         $archived
 * @property string          $delivery_title       Краткое название способа доставки
 * @property string          $delivery_description Развёрнутое название способа доставки
 * @property integer         $delivery_date        Дата доставки
 * @property integer|null    $delivery_from_hour   Время доставки (не раньше)
 * @property integer|null    $delivery_to_hour     Время доставки (не позднее)
 * @property float           $delivery_price
 * @property float           $full_delivery_price
 * @property integer|null    $paid_at              Дата оплаты
 * @property string          $payment_title        Краткое название способа оплаты
 * @property string          $payment_description  Развёрнутое название способа оплаты
 * @property string          $fulfillment_status   Код статуса заказа
 * @property string          $custom_status
 * @property string|null     $comment              Комментарий клиента
 * @property string|null     $manager_comment      Комментарий менеджера
 * @property int             $created_at
 * @property int             $updated_at
 *
 * @property Client          $client
 * @property ShippingAddress $shippingAddress
 * @property OrderPosition[] $orderPositions
 * @property int             $positionCount        Количество позиций в заказе
 */
class Order extends ActiveRecord
{

    public static function tableName()
    {
        return 'insales_order';
    }

    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            [
                'class' => IntegrationDateBehavior::class,
                'columns' => [
                    'created_at' => true,
                    'updated_at' => true,
                    'delivery_date' => false,
                    'paid_at' => false,
                ],
            ],

        ]);
    }

    public function rules()
    {
        return [
            [['company_id', 'client_id', 'number', 'key', 'total_price', 'items_price'], 'required'],
            [['company_id', 'client_id', 'shipping_address_id'], 'integer'],
            [
                'number',
                'filter',
                'filter' => function ($value) {
                    return (string)$value;
                },
            ],
            ['number', 'string', 'max' => 20],
            ['key', 'string', 'length' => 32],
            [['total_price', 'items_price', 'delivery_price', 'full_delivery_price'], 'double'],
            ['currency_code', 'string', 'max' => 3],
            ['archived', 'boolean'],
            [['delivery_title', 'payment_title', 'custom_status'], 'string', 'max' => 100],
            [['delivery_description', 'payment_description'], 'string', 'max' => 500],
            [['delivery_from_hour', 'delivery_to_hour'], 'integer', 'min' => 0, 'max' => 23],
            ['fulfillment_status', 'string', 'max' => 30],
            [['comment', 'manager_comment'], 'string', 'max' => 1000],
        ];
    }

    public function getClient(): ActiveQuery
    {
        return $this->hasOne(Client::class, ['id' => 'client_id']);
    }

    public function getShippingAddress(): ActiveQuery
    {
        return $this->hasOne(ShippingAddress::class, ['id' => 'shipping_address_id']);
    }

    public function getOrderPositions(): ActiveQuery
    {
        return $this->hasMany(OrderPosition::class, ['order_id' => 'id']);
    }

    /**
     * Количество позиций в заказе
     *
     * @return int
     */
    public function getPositionCount(): int
    {
        return $this->getOrderPositions()->count();
    }
}