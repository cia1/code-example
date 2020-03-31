<?php

namespace frontend\modules\integration\models;

use common\models\employee\Employee;
use yii\base\Model;

/**
 * Форма настроек интеграции Вконтакте
 */
class VkSettingsForm extends Model
{

    /** @var int Номер кабинета */
    public $accountId;

    /** @inheritDoc */
    public function rules(): array
    {
        return [
            ['accountId', 'required'],
            ['accountId', 'integer'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'accountId' => 'Номер кабинета',
        ];
    }

    /**
     * Выполняет валидацию и сохраняет конфигурацию в базе данных
     *
     * @param Employee $user
     * @return bool
     */
    public function storeToUser(Employee $user)
    {
        if ($this->validate() !== true) {
            return false;
        }
        return $user->saveIntegration(Employee::INTEGRATION_VK, $this->toArray());
    }

}
