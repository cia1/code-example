<?php

namespace frontend\modules\integration\models\evotor;

use common\models\evotor\Device as DeviceCommon;

class Device extends DeviceCommon
{
    use HookTrait;

    public function getGridColumns(): array
    {
        return [
            'name',
            'store.name',
            'timezone',
        ];
    }

    public function attributeLabels()
    {
        return [
            'name' => 'Название',
            'store.name' => 'Магазин',
            'timezone' => 'Временная зона',
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

    protected static function prepareData(array $data): array
    {
        //В веб-хуке и в API имена полей отличаются
        $data['timezone_offset'] = $data['timezoneOffset'] ?? $data['timezone_offset'];
        $data['store_id'] = $data['storeUuid'] ?? $data['store_id'];
        unset($data['storeUuid'], $data['timezoneOffset']);
        if ($data['store_id']) {
            $data['store_id'] = Store::idByUUID($data['store_id']);
        }
        return $data;
    }

}