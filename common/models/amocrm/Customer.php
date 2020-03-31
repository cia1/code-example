<?php

namespace common\models\amocrm;

use Throwable;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\StaleObjectException;

/**
 * Интеграция AMOcrm: покупатели
 * Внешних связей в базе данных нет, проверки идентификаторов тоже нет, т.к. веб-хуки могут приходить в произвольном порядке.
 *
 * @see https://www.amocrm.ru/developers/content/api/customers
 *
 * @property int      $id
 * @property int      $company_id  Идентификатор компании КУБ
 * @property int      $status_id
 * @property string   $name         Название покупателя
 * @property bool     $deleted      Признак удаления покупателя
 * @property float    $next_price   Планируемая сумма следующей покупки
 * @property int      $periodicity
 * @property int      $next_date    UNIXTIME планируемой следующей покупки
 * @property int      $created_at   UNIXTIME создания примечания
 * @property int      $updated_ay   UNIXTIME последнего изменения примечания
 *
 * @property Note[]   $notes
 * @property-read int notesCount    Количество примечаний
 */
class Customer extends ActiveRecord
{

    public static function tableName()
    {
        return 'amocrm_customer';
    }

    public function rules()
    {
        return [
            ['deleted', 'default', 'value' => false],
            [['next_price', 'periodicity'], 'default', 'value' => 0],
            [['created_at', 'updated_at'], 'default', 'value' => time()],
            [['company_id', 'status_id', 'name', 'deleted', 'next_price', 'periodicity', 'next_date', 'created_at', 'updated_at'], 'required'],
            [['company_id', 'status_id', 'next_date', 'created_at', 'updated_at'], 'integer'],
            ['name', 'string', 'max' => 200],
            ['deleted', 'boolean'],
            ['next_price', 'double', 'min' => 0],
            ['periodicity', 'integer', 'min' => 0, 'max' => 255],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'status_id' => 'Статус',
            'name' => 'Название',
            'deleted' => 'В архиве',
            'next_price' => 'Плановая стоимость заказа',
            'periodicity' => 'Периодичность',
            'next_date' => 'Плановая дата заказ',
            'created_at' => 'Дата создания',
            'updated_at' => 'Дата изменения',
        ];
    }

    /**
     * @return ActiveQuery
     */
    public function getNotes(): ActiveQuery
    {
        return $this->hasMany(Note::class, ['element_id' => 'id'])->andOnCondition(['element_type' => (string)Note::ELEMENT_TYPE_CUSTOMER]);
    }

    /**
     * Количество примечаний, связанных с покупателем
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