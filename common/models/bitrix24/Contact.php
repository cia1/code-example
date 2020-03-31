<?php

namespace common\models\bitrix24;

use common\components\helpers\ArrayHelper;
use common\components\IntegrationDateBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * Интеграция Битрикс24: контакт
 *
 * @property int          $id                  Идентификатор контакта Битрикс24
 * @property int          $company_id          Идентификатор компании КУБ
 * @property int|null     $bitrix24_company_id Идентификатор компании
 * @property string       $type                Тип контакта
 * @property string|null  $honorific           Обращение
 * @property string|null  $post                Должность
 * @property string       $name                Имя
 * @property string       $last_name           Фамилия
 * @property string|null  $second_name         Отчество
 * @property string|null  $comment             Комментарий
 * @property string       $source              Источник контакта
 * @property string|null  $source_description  Комментарий к источнику контакта
 * @property int|null     $birthdate           Дата рождения (UNIXTIME)
 * @property array|string $phone               Номера телефонов
 * @property array|string $email               Адреса электронной почты
 * @property array|string $web                 Адреса сайтов
 * @property array|string $im                  Соц. сети
 * @property int          $created_at          Дата добавления
 * @property int          $updated_at          Дата последнего изменения
 *
 * @property string       $fullName            Полное имя (ФИО)
 * @property Company|null $company
 * @property Invoice[]    $invoices
 * @property int          $invoiceCount        Количество счетов контакта
 */
class Contact extends ActiveRecord
{

    public static function tableName()
    {
        return 'bitrix24_contact';
    }

    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            [
                'class' => IntegrationDateBehavior::class,
                'columns' => [
                    'created_at',
                    'updated_at',
                    'birthdate' => false,
                ],

            ],
        ]);
    }

    public function rules()
    {
        return [
            [['id', 'company_id', 'name', 'last_name'], 'required'],
            [['company_id', 'bitrix24_company_id'], 'integer'],
            [['type', 'honorific', 'post', 'source'], 'string', 'max' => 50],
            [['name', 'last_name', 'second_name'], 'string', 'max' => 30],
            [['comment', 'source_description'], 'string'],
            [
                'phone',
                'filter',
                'filter' => function ($value) {
                    return self::_filterContact($value, Company::PHONE_TYPE);
                },
            ],
            [
                'email',
                'filter',
                'filter' => function ($value) {
                    return self::_filterContact($value, Company::EMAIL_TYPE);
                },
            ],
            [
                'web',
                'filter',
                'filter' => function ($value) {
                    return self::_filterContact($value, Company::WEB_TYPE);
                },
            ],
            [
                'im',
                'filter',
                'filter' => function ($value) {
                    return self::_filterContact($value, Company::IM_TYPE);
                },
            ],
        ];
    }

    /**
     * Декодирует JSON-атрибуты
     */
    public function afterFind()
    {
        if ($this->phone) {
            $this->phone = json_decode($this->phone, true);
        } else {
            $this->phone = [];
        }
        if ($this->email) {
            $this->email = json_decode($this->email, true);
        } else {
            $this->email = [];
        }
        if ($this->web) {
            $this->web = json_decode($this->web, true);
        } else {
            $this->web = [];
        }
        if ($this->im) {
            $this->im = json_decode($this->im, true);
        } else {
            $this->im = [];
        }
        parent::afterFind();
    }

    public function getCompany(): ActiveQuery
    {
        return $this->hasOne(Company::class, ['company_id' => 'company_id', 'id' => 'bitrix24_company_id']);
    }

    /**
     * возаращает полное имя (ФИО)
     *
     * @return string
     */
    public function getFullName(): string
    {
        $name = $this->name . ($this->second_name ? ' ' . $this->second_name : '') . ' ' . $this->last_name;
        if ($this->honorific) {
            $name = $this->honorific . ' ' . $name;
        }
        return $name;
    }

    public function getInvoices(): ActiveQuery
    {
        return $this->hasMany(Invoice::class, ['company_id' => 'company_id', 'contact_id' => 'id']);
    }

    public function getInvoiceCount(): int
    {
        return $this->getInvoices()->count();
    }

    /**
     * @param       $value
     * @param array $validTypes
     * @return string|null
     */
    private static function _filterContact($value, array $validTypes)
    {
        if (is_array($value) === false) {
            return $value;
        }
        foreach ($value as $i => $item) {
            if (isset($item['type']) === false || isset($item['value']) === false) {
                unset($value[$i]);
                continue;
            }
            $item = array_intersect_key($item, ['value' => null, 'type' => null]);
            if (in_array($item['type'], $validTypes) === false) {
                $item['type'] = Company::CONTACT_TYPE_OTHER;
            }
        }
        return json_encode(array_values($value), JSON_UNESCAPED_UNICODE);
    }

}