<?php

namespace common\models\evotor;

use yii\db\ActiveRecord;

/**
 * Интеграция Эвотор: связь сотрудников с магазинами
 *
 * @property int $employee_id ID клиента Эвотор (evotor_employee.id)
 * @property int $store_id    ID магазина (evotor_store.id)
 */
class EmployeeStore extends ActiveRecord
{

    public static function tableName()
    {
        return 'evotor_employee_store';
    }

    public function rules()
    {
        return [
            [['employee_id', 'store_id'], 'required'],
            [['employee_id', 'store_id'], 'integer'],
        ];
    }

}