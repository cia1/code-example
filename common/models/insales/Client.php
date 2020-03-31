<?php

namespace common\models\insales;

use common\components\helpers\ArrayHelper;
use common\components\IntegrationDateBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * Интеграция InSales CMS: клиенты
 *
 * @property int               $id
 * @property int               $company_id Идентификатор компании КУБ
 * @property string            $type
 * @property string            $email
 * @property string            $phone
 * @property string            $name
 * @property string|null       $surname
 * @property string|null       $middlename
 * @property int               $bonus_points
 * @property int               $progressive_discount
 * @property int               $group_discount
 * @property string            $fields_values
 * @property int               $created_at
 * @property int               $updated_at
 *
 * @property string            $fullName   Полное имя клиента, собранное из отдельных атрибутов ФИО
 * @property Order[]           $orders
 * @property int               $orderCount
 * @property ShippingAddress[] $shippingAddresses
 * @property int               $shippingAddressCount
 */
class Client extends ActiveRecord
{

    const TYPE_INDIVIDUAL = 'Client::Individual';
    const TYPE_JURIDICAL = 'Client::Juridical';
    const TYPE = [self::TYPE_INDIVIDUAL, self::TYPE_JURIDICAL];

    public static function tableName()
    {
        return 'insales_client';
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
            [['company_id', 'phone', 'name'], 'required'],
            ['type', 'default', 'value' => self::TYPE_INDIVIDUAL],
            [['bonus_points', 'progressive_discount', 'group_discount'], 'default', 'value' => 0],
            [['company_id', 'bonus_points', 'progressive_discount', 'group_discount'], 'integer'],
            ['type', 'in', 'range' => self::TYPE],
            ['email', 'email'],
            ['phone', 'string', 'max' => 15],
            [['name', 'surname', 'middlename'], 'string', 'max' => 30],
            ['fields_values', 'string'],
        ];
    }

    public function beforeSave($insert)
    {
        if (is_string($this->fields_values) === false) {
            $this->fields_values = json_encode($this->fields_values, JSON_UNESCAPED_UNICODE);
        }
        return parent::beforeSave($insert);
    }

    /**
     * Возвращает ФИО, собирая полное имя из отдельных атрибутов
     *
     * @return string
     */
    public function getFullName(): string
    {
        $value = $this->name;
        if ($this->middlename) {
            $value .= ' ' . $this->middlename;
        }
        if ($this->surname) {
            $value .= ' ' . $this->surname;
        }
        return $value;
    }

    public function getOrders(): ActiveQuery
    {
        return $this->hasMany(Order::class, ['client_id' => 'id']);
    }

    /**
     * Количество заказов этого покупателя
     *
     * @return int
     */
    public function getOrderCount(): int
    {
        return $this->getOrders()->count();
    }

    public function getShippingAddresses(): ActiveQuery
    {
        return $this->hasMany(ShippingAddress::class, ['client_id' => 'id']);
    }

    /**
     * Количество адресов доставки
     *
     * @return int
     */
    public function getShippingAddressCount(): int
    {
        return $this->getShippingAddresses()->count();
    }

}

