<?php


namespace common\models\dossier;


use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * Досье. Данные pb.nalog.ru: задача загрузки дополнительных данных
 * @property int         $inn  ИНН
 * @property string      $type Тип задачи
 * @property array       $relations
 *
 * @property NalogRuCard $card
 */
class NalogRuTask extends ActiveRecord
{

    public static function tableName()
    {
        return '{{%nalogru_task}}';
    }

    public function rules()
    {
        return [
            [['inn', 'type', 'relations'], 'required'],
            ['inn', 'integer', 'max' => 999999999999],
            ['type', 'in', 'range' => NalogRuCard::TYPE],
            [
                'relations',
                'filter',
                'filter' => function ($value) {
                    if (is_string($value) === false) {
                        $value = json_encode($value, JSON_UNESCAPED_UNICODE);
                    }
                    return $value;
                }
            ]
        ];
    }

    public function afterFind()
    {
        /** @noinspection PhpParamsInspection */
        $this->relations = json_decode($this->relations, true);
    }

    public function getCard(): ActiveQuery
    {
        return $this->hasOne(NalogRuCard::class, ['inn' => 'inn'])->inverseOf('taskAddress');
    }
}