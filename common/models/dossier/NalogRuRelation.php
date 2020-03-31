<?php


namespace common\models\dossier;


use yii\db\ActiveRecord;

/**
 * Досье. Данные pb.nalog.ru: связь между юридическми лицами
 * @property int    $inn         ИНН
 * @property string $type        Тип связи
 * @property int    $related_inn Связанная организация (ИНН)
 * @see NalogRuCard::TYPE
 */
class NalogRuRelation extends ActiveRecord
{

    public static function tableName()
    {
        return '{{%nalogru_relation}}';
    }

    public function rules()
    {
        return [
            [['inn', 'type', 'related_inn'], 'required'],
            [['inn', 'related_inn'], 'integer', 'max' => 999999999999],
            ['type', 'in', 'range' => NalogRuCard::TYPE]
        ];
    }

}