<?php

namespace common\models;

use yii\base\InvalidArgumentException;
use yii\db\ActiveRecord;

/**
 * Данные о задолженностях контрагентов, полученные от ФССП (задачи на получение данных с удалённого сервера)
 * @property int         $id
 * @property int|null    $company_id             ID компании, если данные относятся к компании
 * @property int|null    $contractor_id          ID контрагента, если данные относятся к контрагенту
 * @property string      $status                 Состояние загрузки данных
 * @property string      $date                   Дата и время запроса/актуальности данных
 * @property string|null $task                   Идентификатор задача на формирование данных
 *
 * @property string      $entityType             Тип сущности, для которой собираются данные
 * @property int         $entityId               ID сущности
 * @property FsspItem[]  $items
 */
class FsspTask extends ActiveRecord
{

    const RELOAD_TIMEOUT_ERROR = 3600 * 2; //Период (сек), через который данные будут запрошены вновь, если при получении возникла ошибка

    //Тип сущности, к которой привязаны данные
    const ENTITY_TYPE = [self::ENTITY_TYPE_COMPANY, self::ENTITY_TYPE_CONTRACTOR];
    const ENTITY_TYPE_COMPANY = 'company';
    const ENTITY_TYPE_CONTRACTOR = 'contractor';

    //Состояние процесса получения данных с удалённого сервера ФССП
    const STATUS = [
        self::STATUS_REQUEST,
        self::STATUS_ERROR,
        self::STATUS_SUCCESS
    ];
    const STATUS_REQUEST = 'request'; //ещё не готовы, в обработке
    const STATUS_ERROR = 'error'; //загрузить не удалось
    const STATUS_SUCCESS = 'success'; //успешно загружены

    /**
     * Возвращает модель, загружая данные по указанному идентификатору контрагента
     * @param string $entity Сущность, к которой привязаны данные (self::TYPE)
     * @param int    $id     ИД сущности
     * @return self|null
     * @throws InvalidArgumentException
     */
    public static function findByEntity(string $entity, int $id)
    {
        switch ($entity) {
            case self::ENTITY_TYPE_COMPANY:
                return self::findOne(['company_id' => $id]);
            case self::ENTITY_TYPE_CONTRACTOR:
                return self::findOne(['contractor_id' => $id]);
        }
        throw new InvalidArgumentException('Unknown entity "' . $entity . '"');
    }

    public static function tableName()
    {
        return '{{%fssp_task}}';
    }

    public function rules()
    {
        return [
            [['company_id', 'contractor_id'], 'integer'],
            ['status', 'in', 'range' => self::STATUS],
            ['date', 'date', 'format' => 'yyyy-M-d H:m:s'],
            ['task', 'string', 'length' => 36],
        ];
    }

    /**
     * @param string $type
     * @param int    $id
     * @throws InvalidArgumentException
     */
    public function setEntity(string $type, int $id)
    {
        switch ($type) {
            case self::ENTITY_TYPE_COMPANY:
                $this->company_id = $id;
                break;
            case self::ENTITY_TYPE_CONTRACTOR:
                $this->contractor_id = $id;
                break;
            default:
                throw new InvalidArgumentException('Unknown entity "' . $type . '"');
        }
    }

    /**
     * @return string
     */
    public function getEntityType()
    {
        if ($this->company_id !== null) {
            return self::ENTITY_TYPE_COMPANY;
        }
        return self::ENTITY_TYPE_CONTRACTOR;
    }

    /**
     * @return int
     */
    public function getEntityId()
    {
        return $this->{$this->entityType . '_id'};
    }

    public function getItems()
    {
        return $this->hasMany(FsspItem::class, ['task_id' => 'id'])->orderBy([
            'end_date' => SORT_DESC,
            'production_date' => SORT_DESC
        ]);
    }

}
