<?php

namespace common\models\dossier;

use common\components\IntegrationDateBehavior;
use yii\base\InvalidConfigException;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * Досье. Данные pb.nalog.ru: карточка юридического лица
 * Если атрибут $not_exists установлен в TRUE, то в pb.nalog.ru данных по этому ИНН нет, данные во всех остальных полях не актуальны или фиктивные.
 * @see https://pb.nalog.ru/
 * @property int           $inn                     ИНН, первичный ключ ИНН 10/12
 * @property int           $date                    Дата и время последней загрузки данных
 * @property bool          $not_exists              Признак, что по этому контрагенту данных нет
 * @property string        $status_address          Статус загрузки данных проверки по массовым адресам
 * @property string        $title                   Полное наименование организации
 * @property int|null      $inn_date                Дата постановки на учёт в НО
 * @property int|null      $ogrn                    ОГРН
 * @property int|null      $ogrn_date               Дата присвоения ОГРН
 * @property string|null   $creation_method         Способ образования
 * @property string|null   $nalog_department_name   Наименование НО, в котором зарегистрировано ЮЛ
 * @property string|null   $nalog_department_local  Наименование НО по месту нахождения, зарегистрировавшего ЮЛ
 * @property int|null      $kpp                     КПП
 * @property string|null   $okved                   Код ОКВЭД
 * @property int|null      $capital                 Уставной капитал
 * @property int|null      $okopf                   ОКОПФ
 * @property bool          $foreign                 Зарубежная организация
 * @property string        $taxation_form           Форма налогообложения
 * @property bool          $liquidated              ЮЛ ликвидировано
 * @property int|null      $rsmp_category           Категория МСП
 * @property int|null      $rsmp_date               Дата внесения в реестр МСП
 * @property int           $postal_code             Почтовый индекс
 * @property string|null   $region_type             Тип региона
 * @property string        $region_name             Наименование региона
 * @property string|null   $city_type               Тип населённого пункта
 * @property string|null   $city_name               Населённый пункт
 * @property string|null   $street_type             Тип улицы
 * @property string|null   $street_name             Название улицы
 * @property string        $building                Номер дома
 * @property string|null   $block                   Корпус
 * @property string|null   $incorrect_info_comment  Комментарий о недостоверности данных
 *
 * @property string        $address
 * @property NalogRuTask   $taskAddress
 * @property NalogRuCard[] $relatedAddress
 */
class NalogRuCard extends ActiveRecord
{

    const ACTUAL_TIMEOUT = 86400 * 90; //Время актуальности данных для случаев, когда нужно загрузить карточку, но она уже есть

    //Категории реестра МСП
    const MSP = [self::MSP_MICRO, self::MSP_MINI, self::MSP_MIDDLE];
    const MSP_MICRO = 1; //микропредприятие
    const MSP_MINI = 2; //малое предприятие
    const MSP_MIDDLE = 3; //среднее предприятие

    //Формы налогообложения
    const TAXATION = [self::TAXATION_OSN, self::TAXATION_USN, self::TAXATION_ESHN, self::TAXATION_ENVD];
    const TAXATION_OSN = 'osn'; //ОСН
    const TAXATION_USN = 'usn'; //УСН
    const TAXATION_ESHN = 'eshn'; //ЕСХН
    const TAXATION_ENVD = 'envd'; //ЕНВД

    //Статус загрузки дополнительных данных
    const STATUS = [self::STATUS_WITHOUT, self::STATUS_PROCESS, self::STATUS_ERROR, self::STATUS_SUCCESS];
    const STATUS_WITHOUT = 'without'; //данные не загружены
    const STATUS_PROCESS = 'process'; //данные загружаются
    const STATUS_ERROR = 'error'; //в процессе загрузки произошла ошибка, данные загружены не полностью
    const STATUS_SUCCESS = 'success'; //данные успешно загружены

    //Типы связей между юридическими лицами (планируется дополнить в будущем)
    const TYPE = [self::TYPE_ADDRESS];
    const TYPE_ADDRESS = 'address'; //по адресу

    public static function tableName()
    {
        return '{{%nalogru_card}}';
    }

    public static function findByINN(int $inn)
    {
        return static::findOne(['inn' => $inn]);
    }

    public function behaviors()
    {
        return [
            [
                'class' => IntegrationDateBehavior::class,
                'columns' => [
                    'date' => true,
                    'inn_date' => false,
                    'ogrn_date' => false,
                    'rsmp_date' => false,
                ]
            ]
        ];
    }

    public function rules()
    {
        return [
            ['status_address', 'in', 'range' => self::STATUS],
            [['not_exists', 'foreign', 'liquidated'], 'default', 'value' => false],
            ['taxation_form', 'default', 'value' => self::TAXATION_OSN],
            [['inn', 'title', 'postal_code', 'region_name', 'building'], 'required'],
            ['inn', 'integer', 'max' => 999999999999],
            [['title', 'creation_method'], 'string', 'max' => 450],
            ['ogrn', 'integer', 'max' => 9999999999999],
            [['nalog_department_name', 'nalog_department_local'], 'string', 'max' => 250],
            ['kpp', 'integer', 'max' => 999999999],
            ['okved', 'string', 'max' => 8],
            ['capital', 'integer'],
            ['okopf', 'integer', 'max' => 99999],
            [['foreign', 'liquidated'], 'boolean'],
            ['taxation_form', 'in', 'range' => self::TAXATION],
            ['rsmp_category', 'in', 'range' => self::MSP],
            ['postal_code', 'integer', 'max' => 999999],
            [['region_type', 'city_type', 'street_type'], 'string', 'max' => 35],
            [['region_name', 'city_name', 'street_name'], 'string', 'max' => 50],
            [['building', 'block'], 'string', 'max' => 20],
            ['incorrect_info_comment', 'string', 'max' => 500],
        ];
    }

    /**
     * Проверяет актуальность данных
     * @return bool
     */
    public function isActual(): bool
    {
        return time() < $this->date + static::ACTUAL_TIMEOUT;
    }

    /**
     * Устанавливает признак, что в pb.nalog.ru нет информации по этому ИНН
     * Заполняет обязательные поля "пустыми" данными
     */
    public function setNotExists()
    {
        $this->not_exists = true;
        $this->title = '-unknown-';
        $this->postal_code = 0;
        $this->region_name = '-unknown-';
        $this->city_name = '-unknown-';
        $this->building = '-unwn-';
        $this->status_address = self::STATUS_WITHOUT;
    }

    /**
     * Обновляет стутас и сохраняет его в базе данных
     * @param string $status
     */
    public function saveStatusAddress(string $status)
    {
        $this->status_address = $status;
        $this->save(false, ['status_address']);
    }

    /**
     * Добавляет в базу данных связанную карточку
     * @param string $type Тип связи
     * @param int    $inn
     * @see self::TYPE
     */
    public function addRelation(string $type, int $inn)
    {
        $relation = new NalogRuRelation();
        $relation->inn = $this->inn;
        $relation->type = $type;
        $relation->related_inn = $inn;
        $relation->save();
    }

    /**
     * Возвращает адрес
     * @return string
     */
    public function getAddress(): string
    {
        $address = [];
        if ($this->postal_code) {
            $address[] = $this->postal_code;
        }
        if ($this->region_name) {
            $address[] = $this->region_name . ($this->region_type ? ' ' . $this->region_type : '');
        }
        if ($this->city_name) {
            $address[] = ($this->city_type ? $this->city_type . ' ' : '') . $this->city_name;
        }
        if ($this->street_name) {
            $address[] = ($this->street_type ? $this->street_type . ' ' : '') . $this->street_name;
        }
        if ($this->building) {
            $address[] = $this->building . ($this->block ? ' ' . $this->block : '');
        }
        return implode(', ', $address);
    }

    public function getTaskAddress(): ActiveQuery
    {
        return $this->hasOne(NalogRuTask::class, ['inn' => 'inn'])->andWhere(['type' => self::TYPE_ADDRESS])->inverseOf('card');
    }

    /**
     * @return ActiveQuery
     * @throws InvalidConfigException
     */
    public function getRelatedAddress(): ActiveQuery
    {
        return $this->hasMany(static::class, ['inn' => 'related_inn'])->viaTable(NalogRuRelation::tableName(), ['inn' => 'inn']);
    }
}