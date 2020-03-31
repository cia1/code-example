<?php

namespace common\models\amocrm;

use Throwable;
use yii\db\ActiveRecord;
use yii\db\ActiveQuery;
use yii\db\StaleObjectException;

/**
 * Интеграция AMOcrm: контакты
 * Внешних связей в базе данных нет, проверки идентификаторов тоже нет, т.к. веб-хуки могут приходить в произвольном порядке.
 *
 * @see https://www.amocrm.ru/developers/content/api/contacts
 *
 * @property int               $id
 * @property int               $company_id Идентификатор компании КУБ
 * @property string            $name
 * @property int               $created_at
 * @property int               $updated_at
 * @property string|array|null $custom_fields
 * @property string            $type       Тип контакта: contact, company
 * @property int|null          $linked_company_id
 * @property bool              $link_changed
 *
 * @property Note[]            $notes
 * @property-read int          notesCount  Количество примечаний
 */
class Contact extends ActiveRecord
{

    //Тип контакта. Значения и перечень должны соответствовать API AMOcrm
    const TYPE_CONTACT = 'contact';
    const TYPE_COMPANY = 'company';
    const TYPE = [self::TYPE_CONTACT, self::TYPE_COMPANY];

    public static function tableName()
    {
        return 'amocrm_contacts';
    }

    public function rules()
    {
        return [
            [['company_id', 'name'], 'required'],
            ['name', 'string'],
            ['company_id', 'integer'],
            [['created_at', 'updated_at'], 'default', 'value' => time()],
            [['created_at', 'updated_at', 'link_changed'], 'integer'],
            ['type', 'default', 'value' => self::TYPE_CONTACT],
            ['type', 'in', 'range' => static::TYPE],
            ['link_changed', 'boolean'],
            ['custom_fields', 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Название',
            'created_at' => 'Дата создания',
            'updated_at' => 'Дата обновления',
            'linked_company_id' => 'Связанная компания',
        ];
    }

    /**
     * Распаковывает JSON-строку после загрузки данных из БД
     */
    public function afterFind()
    {
        parent::afterFind();
        if ($this->custom_fields !== null) {
            $this->custom_fields = json_decode($this->custom_fields, true);
        }
    }

    /**
     * Запаковывает custom_fields в JSON
     *
     * @inheritDoc
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if (is_array($this->custom_fields) === true) {
                $this->custom_fields = json_encode($this->custom_fields, JSON_UNESCAPED_UNICODE);
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
        return $this->hasMany(Note::class,
            ['element_id' => 'id'])->andOnCondition(['element_type' => (string)($this->type == self::TYPE_COMPANY ? Note::ELEMENT_TYPE_COMPANY : Note::ELEMENT_TYPE_CONTACT)]);
    }

    /**
     * Количество примечаний, связанных с контактом
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