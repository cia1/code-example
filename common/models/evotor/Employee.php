<?php

namespace common\models\evotor;

use common\components\helpers\ArrayHelper;
use common\components\IntegrationDateBehavior;
use yii\db\ActiveRecord;
use yii\db\ActiveQuery;

/**
 * Интеграция Эвотор: сотрудники
 *
 * @see https://developer.evotor.ru/docs/rest_employees.html
 *
 * @property string      $id
 * @property int         $company_id       Идентификатор компании КУБ
 * @property string      $uuid             UUID сотрудника в Эвотор (внешний ID)
 * @property int         $phone            Номер телефона (11-12 цифр)
 * @property string      $role             Роль сотрудника, @see static::ROLE
 * @property string      $name             Имя
 * @property string|null $last_name        Фамилия
 * @property string|null $patronymic_name  Отчество
 * @property int         $created_at       UNIXTIME создания примечания
 * @property int         $updated_at       UNIXTIME последнего изменения примечания
 *
 * @property Store[]     $stores
 *
 */
class Employee extends ActiveRecord
{

    //Доступные роли сотрудников
    const ROLE = [self::ROLE_ADMIN, self::ROLE_CASHIER, self::ROLE_MANUAL];

    const ROLE_ADMIN = 'ADMIN'; //администратор
    const ROLE_CASHIER = 'CASHIER'; //кассир
    const ROLE_MANUAL = 'MANUAL'; //роль, добавленная в личном кабинете

    public static function tableName()
    {
        return 'evotor_employee';
    }

    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            IntegrationDateBehavior::class,
        ]);
    }

    public function rules()
    {
        return [
            [['company_id', 'role', 'name'], 'required'],
            [['company_id', 'phone'], 'integer'],
            ['role', 'in', 'range' => static::ROLE],
            [['name', 'last_name'], 'string', 'max' => 100],
        ];
    }

    public function load($data, $formName = null)
    {
        if (isset($data['stores']) === true && is_array($data['stores']) === true) {
            $data['stores'] = implode(', ', $data['stores']);
        }
        return parent::load($data, $formName);
    }

    public function setStores($value)
    {
        if (is_array($value) === true) {
            $value = implode(', ', $value);
        }
        parent::__set('stores', $value);

    }

    /**
     * Полное ФИО
     *
     * @return string
     */
    public function getFullName(): string
    {
        return $this->name . ($this->patronymic_name ? ' ' . $this->patronymic_name : '') . ($this->last_name ? ' ' . $this->last_name : '');
    }

    public function getEmployeeStores(): ActiveQuery
    {
        return $this->hasMany(EmployeeStore::class, ['employee_id' => 'id']);
    }

    public function getStores(): ActiveQuery
    {
        return $this->hasMany(Store::class, ['id' => 'store_id'])->via('employeeStores');
    }

}