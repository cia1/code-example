<?php

namespace common\models\amocrm;

use Throwable;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\StaleObjectException;

/**
 * Интеграция AMOcrm: сделки
 * Внешних связей в базе данных нет, проверки идентификаторов тоже нет, т.к. веб-хуки могут приходить в произвольном порядке.
 *
 * @see https://www.amocrm.ru/developers/content/api/leads
 *
 * @property int         $id
 * @property int         $company_id      Идентификатор компании КУБ
 * @property int         $status_id       ID статуса
 * @property int         $pipeline_id     ID воронки продаж
 * @property int|null    $main_contact_id ID основного контакта
 * @property int|null    $contact_id      ID связанной компании
 * @property string|null $name            Название сделки
 * @property float       $price           Стоимость сделки
 * @property int         $created_at      Дата создания задачи
 * @property int         $updated_at      Дата последнего изменения задачи
 *
 * @property Note[]      $notes
 * @property-read int    notesCount       Количество примечаний
 * @property Contact     $mainContact
 * @property Contact     $contact
 */
class Lead extends ActiveRecord
{

    public static function tableName()
    {
        return 'amocrm_leads';
    }

    public function rules()
    {
        return [
            [['company_id', 'status_id'], 'required'],
            [['company_id', 'status_id', 'pipeline_id', 'main_contact_id', 'contact_id'], 'integer'],
            ['name', 'string', 'max' => 250],
            ['price', 'double', 'min' => 0],
            [['created_at', 'updated_at'], 'default', 'value' => time()],
            [['created_at', 'updated_at'], 'integer'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'status_id' => 'Статус',
            'pipeline_id' => 'Воронка продаж',
            'name' => 'Название',
            'price' => 'Стоимость',
            'created_at' => 'Дата создания',
            'updated_at' => 'Дата изменения',
        ];
    }

    /**
     * @return ActiveQuery
     */
    public function getNotes(): ActiveQuery
    {
        return $this->hasMany(Note::class, ['element_id' => 'id'])->andOnCondition(['element_type' => (string)Note::ELEMENT_TYPE_LEAD]);
    }

    public function getMainContact(): ActiveQuery
    {
        return $this->hasOne(Contact::class, ['id' => 'main_contact_id']);
    }

    public function getContact(): ActiveQuery
    {
        return $this->hasOne(Contact::class, ['id' => 'contact_id']);
    }

    /**
     * Количество примечаний, связанных со сделкой
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