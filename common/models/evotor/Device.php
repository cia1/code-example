<?php

namespace common\models\evotor;

use yii\db\ActiveRecord;
use yii\db\ActiveQuery;

/**
 * Интегарция Эвотор: смарт-терминалы
 *
 * @see https://developer.evotor.ru/docs/rest_smart_terminals.html
 *
 * @property string      $id
 * @property int         $company_id       Идентификатор компании КУБ
 * @property int         $store_id         ID магазина
 * @property string      $uuid             UUID терминала (внешний ID)
 * @property string      $name             Название терминала
 * @property int         $timezone_offset  Временная зона терминала
 * @property int|null    $imei             IMEI устройства
 * @property string|null $firmware_version Версия ПО
 * @property float|null  $latitude         GPS-координаты смарт-терминала (широта)
 * @property float|null  $longitude        GPS-координаты смарт-терминала (долгота)
 * @property int         $created_at       UNIXTIME создания примечания
 * @property int         $updated_at       UNIXTIME последнего изменения примечания
 *
 * @property Store       $store
 */
class Device extends ActiveRecord
{

    public static function tableName()
    {
        return 'evotor_device';
    }

    public function rules()
    {
        return [
            [['company_id', 'store_id', 'name'], 'required'],
            [['store_id', 'company_id', 'timezone_offset', 'imei'], 'integer'],
            [['latitude', 'longitude'], 'double'],
            ['name', 'string', 'max' => 400],
            ['firmware_version', 'string', 'max' => 11],
        ];
    }

    public function getStore(): ActiveQuery
    {
        return $this->hasOne(Store::class, ['id' => 'store_id']);
    }

    public function getTimezone(): string
    {
        return '+' . $this->timezone_offset / 1000 / 60 / 60;
    }
}