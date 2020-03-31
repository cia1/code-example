<?php

namespace frontend\modules\integration\models\insales;

use common\models\insales\Order as OrderBase;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * @property Client $client
 */
class Order extends OrderBase
{

    /**
     * Создаёт или обновляет данные заказа
     *
     * @param int      $id                Идентификатор заказа InSales
     * @param int      $companyId         Идентификатор клиента КУБ
     * @param int      $clientId          Идентификатор клиента
     * @param int|null $shippingAddressId Идентификатор адреса доставки
     * @param array    $data              Данные клиента
     * @return self|null
     */
    public static function createOrUpdate(int $id, int $companyId, int $clientId, $shippingAddressId, array $data)
    {
        $order = self::findOne(['id' => $id]);
        if ($order === null) {
            $order = new self();
            $order->id = $id;
            $order->company_id = $companyId;
            $order->client_id = $clientId;
        }
        $order->load($data, '');
        $order->shipping_address_id = $shippingAddressId;
        if ($order->save() === false) {
            return null;
        }
        return $order;
    }

    public function getGridColumns(): array
    {
        return [
            'number',
            'client.fullName',
            [
                'attribute' => 'total_price',
                'value' => function (self $model) {
                    return $model->total_price . ' ' . $model->currency_code;
                },
            ],
            [
                'label' => 'Доставка',
                'value' => function (self $model) {
                    $value = $model->delivery_title;
                    if ($model->delivery_date) {
                        $value .= ' ' . date('d.m.Y', $model->delivery_date);
                    }
                    if ($model->delivery_from_hour) {
                        $value .= ' с ' . $model->delivery_from_hour . ':00';
                    }
                    if ($model->delivery_to_hour) {
                        $value .= ' до ' . $model->delivery_to_hour . ':00';
                    }
                    return $value;

                },
                'format' => 'raw',
            ],
            [
                'attribute' => 'delivery_price',
                'value' => function (self $model) {
                    return $model->delivery_price . ' ' . $model->currency_code;
                },
            ],
            'shippingAddress.full_delivery_address',
            [
                'label' => 'Оплата',
                'value' => function (self $model) {
                    $value = $model->payment_title;
                    $value .= '<br><strong>';
                    $value .= $model->paid_at ? '(оплата ' . date('d.m.Y', $model->paid_at) . ')' : '(не оплачен)';
                    $value .= '</strong>';
                    return $value;
                },
                'format' => 'raw',
            ],
            'status',
            [
                'label' => 'Комментарий',
                'value' => function (self $model) {
                    $value = [];
                    if ($model->comment) {
                        $value[] = '<strong>Клиент</strong>: ' . $model->comment;
                    }
                    if ($model->manager_comment) {
                        $value[] = '<strong>Менеджер</strong>: ' . $model->manager_comment;
                    }
                    return implode('<br />', $value);
                },
                'format' => 'raw',
            ],
            'date',
            [
                'attribute' => 'positionCount',
                'value' => function (self $model) {
                    return Html::a($model->positionCount, Url::to(['/integration/insales/order/' . $model->id . '/position']));
                },
                'format' => 'raw',
            ],
        ];
    }

    public function attributeLabels()
    {
        return [
            'number' => 'Номер',
            'client.fullName' => 'Клиент',
            'total_price' => 'Сумма',
            'shippingAddress.full_delivery_address' => 'Адрес доставки',
            'delivery_price' => 'Стоимость доставки',
            'status' => 'Статус',
            'date' => 'Дата',
            'positionCount' => 'Товары',
        ];
    }

    public function getStatus(): string
    {
        return $this->custom_status ?? $this->fulfillment_status;
    }

    public function getDate(): string
    {
        return date('d.m.Y H:i:s', $this->created_at);
    }
}