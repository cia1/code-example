<?php

namespace frontend\modules\integration\models;

use common\models\employee\Employee;
use yii\base\Model;

/**
 * Форма настроек интеграции Битрикс 24
 */
class Bitrix24SettingsForm extends Model
{

    /** @var string Имя хоста */
    public $host;

    /** @inheritDoc */
    public function rules(): array
    {
        return [
            ['host', 'required'],
            [
                'host',
                'filter',
                'filter' => function (string $value) {
                    if (substr($value, -1) === '/') {
                        $value = substr($value, 0, -1);
                    }
                    $value = str_replace('https://', '', $value);
                    if (strtolower(substr($value, -12)) === '.bitrix24.ru') {
                        $value = mb_substr($value, 0, -12);
                    }
                    return $value;
                },
            ],
            ['host', 'match', 'pattern' => '/^[A-Za-z0-9_\.-]+$/', 'message' => 'Поле "Ваш домен" может содержать только символы a-z, 0-9,_ и .'],
        ];
    }

    /** @inheritDoc */
    public function attributeLabels()
    {
        return [
            'host' => 'Ваш домен Битрикс24 (XXX.bitrix24.ru)',
        ];
    }

    /**
     * Выполняет валидацию и сохраняет конфигурацию в базе данных
     *
     * @param Employee $employee
     * @return bool
     */
    public function storeToUser(Employee $employee)
    {
        if ($this->validate() !== true) {
            return false;
        }
        return $employee->saveIntegration(Employee::INTEGRATION_BITRIX24, $this->toArray());
    }

}
