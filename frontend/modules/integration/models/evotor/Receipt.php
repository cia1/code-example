<?php

namespace frontend\modules\integration\models\evotor;

use common\models\evotor\Receipt as ReceiptCommon;
use yii\helpers\Html;
use yii\helpers\Url;

class Receipt extends ReceiptCommon
{
    use HookTrait;

    public function getGridColumns(): array
    {
        return [
            'device.name',
            'store.name',
            [
                'attribute' => 'date_time',
                'value' => function (self $model) {
                    return date('d.m.Y H:i:s', $model->date_time);
                },
            ],
            'typeLabel',
            'evotorEmployee.fullName',
            'total_tax',
            'total_discount',
            'total_amount',
            [
                'attribute' => 'itemsCount',
                'label' => 'Позиций в чеке',
                'value' => function (self $model) {
                    return Html::a($model->itemsCount, Url::to('/integration/evotor/receipt/' . $model->id));
                },
                'format' => 'raw',
            ],
        ];
    }

    public function attributeLabels()
    {
        return [
            'device.name' => 'Терминал',
            'store.name' => 'Магазин',
            'date_time' => 'Дата и время',
            'typeLabel' => 'Операция',
            'evotorEmployee.fullName' => 'Сотрудник',
            'total_tax' => 'Налог',
            'total_discount' => 'Скидка',
            'total_amount' => 'Итого',
        ];
    }

    public function getTypeLabel(): string
    {
        switch ($this->type) {
            default:
            case self::TYPE_SELL:
                return 'продажа';
            case self::TYPE_PAYBACK:
                return 'возврат';
        }
    }

    /**
     * Кастомный обработчик веб-хука
     * Обрабатывает связанные позиции чека
     *
     * @param int    $companyId
     * @param array  $data
     * @param string $path
     * @return bool
     */
    public function hook(int $companyId, array $data, string $path): bool
    {
        $data = $data['data'];
        $data['cash'] = $data['paymentSource'] !== self::PAYMENT_TYPE_CARD;
        $data = static::prepareData($data);
        $this->load($data, '');
        $this->uuid = $data['id'] ?? $data['uuid'];
        $this->id = null;
        $this->company_id = $companyId;
        $this->parsePath($path);
        if ($this->save() === false) {
            return false;
        }
        if (isset($data['items']) === false) {
            return true;
        }
        $result = true;
        foreach ($data['items'] as $item) {
            $receiptItem = new ReceiptItem();
            $receiptItem->receipt_id = $this->id;
            $result = $receiptItem->hook($companyId, $item, '');
        }
        return $result;
    }

    /**
     * Ищет ID (первичный ключ) по UUID
     *
     * @param string|null $uuid
     * @return int|null
     */
    public static function idByUUID($uuid)
    {
        if ($uuid === null) {
            return null;
        }
        $id = static::find()->where(['uuid' => $uuid])->select('id')->one();
        if ($id !== null) {
            $id = (int)$id['id'];
        }
        return $id;
    }

    /**
     * Если uuid задан, то выполняет UPDATE, иначе - INSERT
     *
     * @inheritDoc
     */
    public function save($runValidation = true, $attributeNames = null)
    {
        if ($this->uuid !== null) {
            $id = static::idByUUID($this->uuid);
            $this->isNewRecord = $id === null;
            if ($id !== null) {
                $this->id = $id;
            }
        }
        return parent::save($runValidation, $attributeNames);
    }

    /** @inheritDoc */
    protected static function prepareData(array $data): array
    {
        if (array_key_exists('deviceId', $data) === true) {
            $data['device_id'] = $data['deviceId'];
            unset($data['deviceId']);
        }
        if (array_key_exists('storeId', $data) === true) {
            $data['store_id'] = $data['storeId'];
            unset($data['storeId']);
        }
        if (array_key_exists('dateTime', $data) === true) {
            $data['date_time'] = $data['dateTime'];
            unset($data['dateTime']);
        }
        if (array_key_exists('employeeId', $data) === true) {
            $data['evotor_employee_id'] = $data['employeeId'];
            unset($data['employeeId']);
        }
        if (array_key_exists('totalTax', $data) === true) {
            $data['total_tax'] = $data['totalTax'];
            unset($data['totalTax']);
        }
        if (array_key_exists('totalDiscount', $data) === true) {
            $data['total_discount'] = $data['totalDiscount'];
            unset($data['totalDiscount']);
        }
        if (array_key_exists('totalAmount', $data) === true) {
            $data['total_amount'] = $data['totalAmount'];
            unset($data['totalAmount']);
        }
        if (isset($data['device_id']) === true) {
            $data['device_id'] = Device::idByUUID($data['device_id']);
        }
        if (isset($data['store_id']) === true) {
            $data['store_id'] = Store::idByUUID($data['store_id']);
        }
        if (isset($data['evotor_employee_id']) === true) {
            $data['evotor_employee_id'] = Employee::idByUUID($data['evotor_employee_id']);
        }
        return $data;
    }

}