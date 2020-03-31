<?php

namespace frontend\modules\integration\models\evotor;

use common\models\evotor\Store as StoreCommon;

class Store extends StoreCommon
{
    use HookTrait;

    public function getGridColumns(): array
    {
        return [
            'name',
            'address',
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

}