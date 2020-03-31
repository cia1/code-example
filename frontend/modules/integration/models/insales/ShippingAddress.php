<?php

namespace frontend\modules\integration\models\insales;

use common\models\insales\ShippingAddress as ShippingAddressBase;

class ShippingAddress extends ShippingAddressBase
{

    /**
     * Создаёт или обновляет адрес доставки
     *
     * @param int   $id       Идентификатор адреса доставки в InSales
     * @param int   $clientId Идентификатор клиента InSales
     * @param array $data     Данные адреса доставки
     * @return self|null
     */
    public static function createOrUpdate(int $id, int $clientId, array $data)
    {
        $shippingAddress = self::findOne(['id' => $id]);
        if ($shippingAddress === null) {
            $shippingAddress = new self();
            $shippingAddress->id = $id;
            $shippingAddress->client_id = $clientId;
        }
        $shippingAddress->load($data, '');
        if ($shippingAddress->save() === false) {
            return null;
        }
        return $shippingAddress;
    }

    public function getGridColumns(): array
    {
        return [
            'fullName',
            'phone',
            'full_delivery_address',
            'latitude',
            'longitude',
        ];
    }

    public function attributeLabels()
    {
        return [
            'fullName' => 'Имя',
            'phone' => 'Телефон',
            'full_delivery_address' => 'Адрес',
            'latitude' => 'Широта',
            'longitude' => 'Долгота',
        ];
    }

}