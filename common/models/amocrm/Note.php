<?php

namespace common\models\amocrm;

use yii\db\ActiveRecord;

/**
 * Интеграция AMOcrm: примечания
 * Внешних связей в базе данных нет, проверки идентификаторов тоже нет, т.к. веб-хуки могут приходить в произвольном порядке.
 *
 * @see https://www.amocrm.ru/developers/content/api/notes
 *
 * @property int    $id
 * @property int    $company_id   Идентификатор компании КУБ
 * @property int    $element_id   ID сущности, к которой относится примечание
 * @property string $element_type Тип сущности, к которой относится примечание, @see self::ELEMENT_TYPE
 * @property string $note_type    Тип примечания, @see self::TYPE
 * @property string $text         Текст примечания
 * @property string $attachement  Вложения
 * @property int    $created_at   UNIXTIME создания примечания
 * @property int    $updated_at   UNIXTIME последнего изменения примечания
 *
 */
class Note extends ActiveRecord
{

    /*
     * Типы сущностей, к котором привязано примечание. Значения и перечень должны соответствовать API AMOcrm
     *
     * @see https://www.amocrm.ru/developers/content/api/notes#element_types
     */
    const ELEMENT_TYPE_CONTACT = 1; //контакт
    const ELEMENT_TYPE_LEAD = 2; //сделка
    const ELEMENT_TYPE_COMPANY = 3; //компания
    const ELEMENT_TYPE_TASK = 4; //задача
    const ELEMENT_TYPE_CUSTOMER = 12; //покупатель
    const ELEMENT_TYPE = [self::ELEMENT_TYPE_CONTACT, self::ELEMENT_TYPE_LEAD, self::ELEMENT_TYPE_COMPANY, self::ELEMENT_TYPE_TASK, self::ELEMENT_TYPE_CUSTOMER];

    /**
     * Типы примечаний. Значения и перечень должны соответствовать API AMOcrm
     *
     * @see https://www.amocrm.ru/developers/content/api/notes#note_types
     */
    const TYPE_COMMON = 4; //обычное примечание
    const TYPE_TASK_RESULT = 13; //результат по задаче
    const TYPE_SYSTEM = 25; //системное сообщение
    const TYPE_SMS_IN = 102; //входящее СМС
    const TYPE_SMS_OUT = 103; //исходящее СМС
    const TYPE = [self::TYPE_COMMON, self::TYPE_TASK_RESULT, self::TYPE_SYSTEM, self::TYPE_SMS_IN, self::TYPE_SMS_OUT];

    public static function tableName()
    {
        return 'amocrm_note';
    }

    public function rules()
    {
        return [
            [['created_at', 'updated_at'], 'default', 'value' => time()],
            [['company_id', 'element_id', 'element_type', 'note_type', 'text', 'created_at', 'updated_at'], 'required'],
            [['company_id', 'element_id'], 'integer'],
            ['element_type', 'in', 'range' => static::ELEMENT_TYPE],
            ['note_type', 'in', 'range' => static::TYPE],
            [['text', 'attachement'], 'string', 'max' => 1000],
            [['created_at', 'updated_at'], 'integer'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'note_type' => 'Тип примечания',
            'text' => 'Текст',
            'attachement' => 'Вложения',
            'created_at' => 'Дата создания',
            'updated_at' => 'Дата изменения',
        ];
    }

}