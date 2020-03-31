<?php

namespace common\models\amocrm;

use Throwable;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\StaleObjectException;

/**
 * Интеграция AMOcrm: задачи
 * Внешних связей в базе данных нет, проверки идентификаторов тоже нет, т.к. веб-хуки могут приходить в произвольном порядке.
 *
 * @see https://www.amocrm.ru/developers/content/api/tasks
 *
 * @property int               $id
 * @property int               $company_id      Идентификатор компании КУБ
 * @property int|null          $element_id      Идентификатор связанной с задачей сущнтости
 * @property string|null       $element_type    Тип связанной с задачей сущности
 * @property int               $task_type       Тип задачи, @see self::TYPE
 * @property string|null       $text            Текст задачи
 * @property bool              $status          Признак завешения задачи
 * @property int               $complete_before Дата заверешения (от)
 * @property int               $complete_till   Дата завершения (до)
 * @property int               $created_at      Дата создания задачи
 * @property int               $updated_at      Дата последнего изменения задачи
 * @property string|array|null $result          Результат задачи, JSON-строка
 *
 * @property Note[]            $notes
 * @property-read int          notesCount       Количество примечаний
 */
class Task extends ActiveRecord
{

    //Типы сущностей, к котором привязана задача. Значения и перечень должны соответствовать API AMOcrm
    const ELEMENT_TYPE_CONTACT = 1; //контакт
    const ELEMENT_TYPE_LEAD = 2; //сделка
    const ELEMENT_TYPE_COMPANY = 3; //компания
    const ELEMENT_TYPE_CUSTOMER = 12; //покупатель
    const ELEMENT_TYPE = [self::ELEMENT_TYPE_CONTACT, self::ELEMENT_TYPE_LEAD, self::ELEMENT_TYPE_COMPANY, self::ELEMENT_TYPE_CUSTOMER];

    /**
     * Типы задачи. Значения и перечень должны соответствовать API AMOcrm
     *
     * @see https://www.amocrm.ru/developers/content/api/tasks#type
     */
    const TYPE_CALL = 1; //звонок
    const TYPE_MEETING = 2; //встреча
    const TYPE_LETTER = 3; //написать письмо
    const TYPE = [self::TYPE_CALL, self::TYPE_MEETING, self::TYPE_LETTER];

    public static function tableName()
    {
        return 'amocrm_task';
    }

    public function rules()
    {
        return [
            [['company_id', 'task_type', 'complete_before', 'complete_till'], 'required'],
            [['company_id', 'element_id'], 'integer'],
            ['element_type', 'in', 'range' => static::ELEMENT_TYPE],
            ['task_type', 'in', 'range' => static::TYPE],
            ['text', 'string', 'max' => 1000],
            ['status', 'default', 'value' => false],
            ['status', 'boolean'],
            [
                ['complete_before', 'complete_till'],
                'filter',
                'filter' => function ($value) { //преобразовать дату к UNIXTIME, если она в строковом формате
                    if (is_int($value) === false && ctype_digit($value) === false) {
                        $value = strtotime($value);
                    }
                    return $value;
                },
            ],
            [['complete_before', 'complete_till'], 'integer'],
            [['created_at', 'updated_at'], 'default', 'value' => time()],
            [['created_at', 'updated_at'], 'integer'],
            ['result', 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'task_type' => 'Тип задачи',
            'text' => 'Текст',
            'status' => 'Статус',
            'complete_before' => 'Завершить от',
            'complete_till' => 'Завершить до',
            'created_at' => 'Дата создания',
            'updated_at' => 'Дата изменения',
            'result' => 'Результат',
        ];
    }

    /**
     * Распаковывает JSON-строку после загрузки данных из БД
     */
    public function afterFind()
    {
        parent::afterFind();
        if ($this->result !== null) {
            $this->result = json_decode($this->result, true);
        }
    }

    /**
     * Запаковывает result в JSON
     *
     * @inheritDoc
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if (is_array($this->result) === true) {
                $this->result = json_encode($this->result, JSON_UNESCAPED_UNICODE);
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return ActiveQuery
     */
    public function getNotes(): ActiveQuery
    {
        return $this->hasMany(Note::class, ['element_id' => 'id'])->andOnCondition(['element_type' => (string)Note::ELEMENT_TYPE_TASK]);
    }

    /**
     * Количество примечаний, связанных с задачей
     *
     * @return int
     */
    public function getNotesCount(): int
    {
        return $this->getNotes()->count();
    }

    /**
     * Т.к. вебхуки могут приходить в разной последовательности (сналча создание примечания, потом создание/изменение сущности),
     * то в СУБД нельзя создать Foreign Key, поэтому связанные сущности нужно удалять вручную.
     *
     * @inheritDoc
     * @throws Throwable
     * @throws StaleObjectException
     */
    public function beforeDelete()
    {
        foreach ($this->notes as $note) {
            $note->delete();
        }
        return parent::beforeDelete();
    }
}