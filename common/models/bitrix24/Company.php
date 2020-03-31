<?php

namespace common\models\bitrix24;

use common\components\helpers\ArrayHelper;
use common\components\IntegrationDateBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * Интеграция Битрикс24: компании
 *
 * @property int          $id            Идентификатор компании Битрикс24
 * @property int          $company_id    Идентификатор клиента КУБ
 * @property string       $type          Тип компании
 * @property string       $title         Название
 * @property string|null  $logo          URL логотипа
 * @property string       $industry      Сфера деятельности
 * @property float|null   $revenue       Годовой оборот
 * @property string       $currency      Валюта
 * @property array|string $phone         Номера телефонов
 * @property array|string $email         Адреса электронной почты
 * @property array|string $web           Адреса сайтов
 * @property array|string $im            Соц. сети
 * @property integer      $created_at    Дата и время добавления
 * @property integer      $updated_at    Дата и время последнего измененеия
 *
 * @property Invoice[]    $invoices
 * @property int          $invoiceCount  Количество счетов компании
 *
 */
class Company extends ActiveRecord
{

    //Типы контактных данных
    const CONTACT_TYPE_WORK = 'WORK'; //рабочий, корпоративный
    const CONTACT_TYPE_MOBILE = 'MOBILE'; //мобильный
    const CONTACT_TYPE_FAX = 'FAX'; //факс
    const CONTACT_TYPE_HOME = 'HOME'; //домашний, частный
    const CONTACT_TYPE_PAGER = 'PAGER'; //пейджер
    const CONTACT_TYPE_MAILING = 'MAILING'; //для рассылки
    const CONTACT_TYPE_FACEBOOK = 'FACEBOOK'; //facebook
    const CONTACT_TYPE_VK = 'VK'; //ВКонтакте
    const CONTACT_TYPE_LIVEJOURNAL = 'LIVEJOURNAL'; //LiveJournal
    const CONTACT_TYPE_TWITTER = 'TWITTER'; //Twitter
    const CONTACT_TYPE_TELEGRAM = 'TELEGRAM'; //Telegram
    const CONTACT_TYPE_SKYPE = 'SKYPE'; //Skype
    const CONTACT_TYPE_VIBER = 'VIBER'; //Viber
    const CONTACT_TYPE_INSTAGRAM = 'INSTAGRAM'; //Instagram
    const CONTACT_TYPE_BITRIX24 = 'BITRIX24'; //Bitrix24
    const CONTACT_TYPE_OPENLINE = 'OPENLINE'; //Онлайн-чат
    const CONTACT_TYPE_IMOL = 'IMOL'; //Открытая линия
    const CONTACT_TYPE_OTHER = 'OTHER'; //другой

    //Типы контактных телефонов
    const PHONE_TYPE = [
        self::CONTACT_TYPE_WORK,
        self::CONTACT_TYPE_MOBILE,
        self::CONTACT_TYPE_FAX,
        self::CONTACT_TYPE_HOME,
        self::CONTACT_TYPE_PAGER,
        self::CONTACT_TYPE_MAILING,
        self::CONTACT_TYPE_OTHER,
    ];
    //Типы контактов электронной почты
    const EMAIL_TYPE = [
        self::CONTACT_TYPE_WORK,
        self::CONTACT_TYPE_HOME,
        self::CONTACT_TYPE_MAILING,
        self::CONTACT_TYPE_OTHER,
    ];
    //Типы адресов сайтов
    const WEB_TYPE = [
        self::CONTACT_TYPE_WORK,
        self::CONTACT_TYPE_HOME,
        self::CONTACT_TYPE_FACEBOOK,
        self::CONTACT_TYPE_VK,
        self::CONTACT_TYPE_LIVEJOURNAL,
        self::CONTACT_TYPE_TWITTER,
        self::CONTACT_TYPE_OTHER,
    ];
    //Типы адресов социальных сетей
    const IM_TYPE = [
        self::CONTACT_TYPE_FACEBOOK,
        self::CONTACT_TYPE_TELEGRAM,
        self::CONTACT_TYPE_VK,
        self::CONTACT_TYPE_SKYPE,
        self::CONTACT_TYPE_VIBER,
        self::CONTACT_TYPE_INSTAGRAM,
        self::CONTACT_TYPE_BITRIX24,
        self::CONTACT_TYPE_OPENLINE,


    ];

    public static function tableName()
    {
        return 'bitrix24_company';
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
            [['id', 'company_id', 'title'], 'required'],
            ['company_id', 'integer'],
            [['type', 'industry'], 'string', 'max' => 50],
            ['title', 'string', 'max' => 300],
            ['logo', 'string', 'max' => 500],
            ['revenue', 'double', 'min' => 0],
            ['currency', 'string', 'length' => 3],
            [
                'phone',
                'filter',
                'filter' => function ($value) {
                    return self::_filterContact($value, self::PHONE_TYPE);
                },
            ],
            [
                'email',
                'filter',
                'filter' => function ($value) {
                    return self::_filterContact($value, self::EMAIL_TYPE);
                },
            ],
            [
                'web',
                'filter',
                'filter' => function ($value) {
                    return self::_filterContact($value, self::WEB_TYPE);
                },
            ],
            [
                'im',
                'filter',
                'filter' => function ($value) {
                    return self::_filterContact($value, self::IM_TYPE);
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

    public function getInvoices(): ActiveQuery
    {
        return $this->hasMany(Invoice::class, ['company_id' => 'company_id', 'bitrix24_company_id' => 'id']);
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
                $item['type'] = self::CONTACT_TYPE_OTHER;
            }
        }
        return json_encode(array_values($value), JSON_UNESCAPED_UNICODE);
    }
}