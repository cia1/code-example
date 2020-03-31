<?php

namespace common\models\amocrm;

use common\models\Company;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * Интеграция AMOcrm: связь пользователей AMOcrm с покупателями сайта
 *
 * @property int      $user_id
 * @property int      $company_id
 *
 * @property Company $company
 */
class User extends ActiveRecord
{

    public static function tableName()
    {
        return 'amocrm_user';
    }

    public function rules()
    {
        return [
            [['user_id', 'company_id'], 'required'],
            [['user_id', 'company_id'], 'integer'],
        ];
    }

    public function getCompany(): ActiveQuery
    {
        return $this->hasOne(Company::class, ['id' => 'company_id']);
    }

}