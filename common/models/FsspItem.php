<?php

namespace common\models;

use yii\db\ActiveRecord;

/**
 * Данные о задолженностях контрагентов, полученные от ФССП (исполнительные производства)
 * @property int         $id
 * @property int         $task_id           ID задачи, по которой были загружены данные
 * @property string      $name              Название контрагента, полученное от ФССП
 * @property string      $production_number Номер исполнительного производства
 * @property string      $production_date   Дата исполнительного производства
 * @property string      $subject
 * @property string      $detail            Описание документа
 * @property string      $department        Отделение ФССП
 * @property string      $bailiff           Судебный пристав
 * @property string|null $end_date          Дата завершения производства, если NULL, производство не завершено
 * @property string|null $end_base          Основание завершения (статья, пункт, подпункт)
 */
class FsspItem extends ActiveRecord
{

    public static function tableName()
    {
        return '{{%fssp_item}}';
    }

    public function rules()
    {
        return [
            ['task_id', 'required'],
            ['task_id', 'integer'],
            [['production_date', 'end_date'], 'date', 'format' => 'yyyy-M-d'],
            ['name', 'string', 'max' => 250],
            ['production_number', 'string', 'min' => 16, 'max' => 19],
            [['subject', 'department', 'bailiff'], 'string', 'max' => 300],
            ['detail', 'string', 'max' => 1000],
            ['end_base', 'string', 'max' => 100]
        ];
    }

    public function beforeSave($insert)
    {
        $this->end_base = str_replace(' ', '', $this->end_base);
        return parent::beforeSave($insert);
    }

    public function endBaseString()
    {
        if (!$this->end_base) {
            return '';
        }
        $base = explode(',', $this->end_base);
        return '229-ФЗ ст. ' . $base[0] . (isset($base[1]) === true ? ' п.' . $base[1] : '') . (isset($base[2]) === true ? ' п.п.' . $base[2] : '');
    }

}