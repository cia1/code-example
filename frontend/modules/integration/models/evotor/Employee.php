<?php

namespace frontend\modules\integration\models\evotor;

use common\models\evotor\Employee as EmployeeCommon;
use common\models\evotor\EmployeeStore;
use yii\db\IntegrityException;

class Employee extends EmployeeCommon
{
    use HookTrait;

    /** @inheritDoc */
    public function hook(int $companyId, array $data,/** @noinspection PhpUnusedParameterInspection */ string $path): bool
    {
        $data = static::prepareData($data);
        $this->load($data, '');
        $this->uuid = $data['id'] ?? $data['uuid'];
        $this->id = null;
        $this->company_id = $companyId;
        if ($this->save() === false) {
            return false;
        }
        EmployeeStore::deleteAll(['employee_id' => $this->id]);
        foreach ($data['stores'] as $item) {
            $item = Store::idByUUID($item);
            $employeeStore = new EmployeeStore();
            $employeeStore->employee_id = $this->id;
            $employeeStore->store_id = $item;
            try {
                $employeeStore->save();
            } /** @noinspection PhpRedundantCatchClauseInspection */ catch (IntegrityException $e) {
            }
        }
        return true;
    }

    public function getGridColumns(): array
    {
        return [
            [
                'attribute' => 'fullName',
                'label' => 'Имя',
            ],
            'role',
            'phone',
            [
                'attribute' => 'stores',
                'value' => function (self $model) {
                    $value = '';
                    foreach ($model->stores as $item) {
                        if ($value !== '') {
                            $value .= ', ';
                        }
                        $value .= $item->name;
                    }
                    return $value;
                },
            ],
        ];
    }

    /**
     * Ищет ID (первичный ключ) по UUID
     *
     * @param string|null $uuid
     * @return int|null
     */
    public static function idByUUID($uuid)
    {
        if ($uuid === null) {
            return null;
        }
        $id = static::find()->where(['uuid' => $uuid])->select('id')->one();
        if ($id !== null) {
            $id = (int)$id['id'];
        }
        return $id;
    }

    /**
     * Если uuid задан, то выполняет UPDATE, иначе - INSERT
     *
     * @inheritDoc
     */
    public function save($runValidation = true, $attributeNames = null)
    {
        if ($this->uuid !== null) {
            $id = static::idByUUID($this->uuid);
            $this->isNewRecord = $id === null;
            if ($id !== null) {
                $this->id = $id;
            }
        }
        return parent::save($runValidation, $attributeNames);
    }

    public function attributeLabels()
    {
        return [
            'role' => 'Роль',
            'phone' => 'Телефон',
            'stores' => 'Магазины',
        ];
    }

    /** @inheritDoc */
    protected static function prepareData(array $data): array
    {
        if (array_key_exists('lastName', $data) === true) {
            $data['patronymic_name'] = $data['lastName'];
            unset($data['lastName']);
        }
        if (array_key_exists('patronymicName', $data) === true) {
            $data['last_name'] = $data['lastName'];
            unset($data['patronymicName']);
        }
        return $data;
    }

}