<?php

namespace common\models;

use yii\db\ActiveRecord;

/**
 * Лог запросов API ЗАЧЕСТНЫЙБИЗНЕС
 * @property int    contractorId ID контрагента, для которого выполняется проверка
 * @property string $date        Дата проверки
 * @property string $method      Запрошенный API-метод
 * @property string $query       Параметры запроса
 * @property bool   $success     Был ли запрос успешен (успешный ответ от API)
 */
class ZchbLog extends ActiveRecord
{

    public static function tableName()
    {
        return 'zchb_log';
    }

    public function rules()
    {
        return [
            ['date', 'default', 'value' => date('Y-m-d H:i:s')],
            [['contractor_id', 'method', 'query'], 'required'],
            ['contractor_id', 'integer'],
            ['date', 'datetime'],
            [['method', 'query'], 'string'],
            ['success', 'boolean']
        ];
    }

}