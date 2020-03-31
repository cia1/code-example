<?php

namespace common\models\insales;

use yii\db\ActiveRecord;

/**
 * Интеграция InSales CMS: адреса доставки
 *
 * @property int         $id
 * @property int         $client_id
 * @property string      $name                  Имя получателя
 * @property string|null $surname               Фамилия получателя
 * @property string|null $middlename            Отчество получателя
 * @property string      $phone                 Контактный номер телефона
 * @property string      $full_delivery_address Полный адрес доставки
 * @property float       $latitude              Координаты (широта)
 * @property float       $longitude             Координаты (долгота)
 *
 * @property string      $fullName              Полное имя получателя, собранное из отдельных атрибутов (ФИО)
 */
class ShippingAddress extends ActiveRecord
{

    public static function tableName()
    {
        return 'insales_shipping_address';
    }

    public function rules()
    {
        return [
            [['client_id', 'name', 'phone', 'full_delivery_address'], 'required'],
            ['client_id', 'integer'],
            [['name', 'surname', 'middlename'], 'string', 'max' => 30],
            ['phone', 'string', 'max' => 15],
            ['full_delivery_address', 'string', 'max' => 300],
            [['latitude', 'longitude'], 'double'],
        ];
    }

    /**
     * Возвращает ФИО, собирая полное имя из отдельных атрибутов
     *
     * @return string
     */
    public function getFullName():string {
        $value = $this->name;
        if ($this->middlename) {
            $value .= ' ' . $this->middlename;
        }
        if ($this->surname) {
            $value .= ' ' . $this->surname;
        }
        return $value;
    }

}