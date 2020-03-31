<?php

namespace frontend\modules\integration\models\evotor;

use common\models\evotor\Product as ProductCommon;

class Product extends ProductCommon
{

    use HookTrait;

    public function getTypeLabel()
    {
        switch ($this->type) {
            case self::TYPE_NORMAL:
            default:
                return 'обычный';
            case self::TYPE_ALCOHOL_MARKED:
                return 'алкоголь марк.';
            case self::TYPE_ALCOHOL_NOT_MARKED:
                return 'алкоголь не марк.';
            case self::TYPE_SERVICE:
                return 'услуга';
            case self::TYPE_TOBACCO_MARKED:
                return 'табак марк.';
        }
    }

    public function getTaxLabel()
    {
        switch ($this->tax) {
            case self::TAX_VAT_0:
                return '0%';
            case self::TAX_NO_VAT:
                return 'без НДС';
            case self::TAX_VAT_18:
                return '18% / 20%';
            case self::TAX_VAT_10:
                return '10%';
            case self::TAX_VAT_10_110:
                return '10%/110%';
            case self::TAX_VAT_18_118:
                return '18%/118%';
            case self::TAX_VAT_20:
                return '20%';
            case self::TAX_VAT_20_120:
                return '20%/120%';
            default:
                return '';
        }
    }

    public function getGridColumns(): array
    {
        return [
            'name',
            [
                'attribute' => 'typeLabel',
                'label' => 'Тип',
            ],
            [
                'label' => 'Остаток',
                'value' => function (self $model) {
                    return $model->quantity . ' ' . $model->measure_name;
                },
            ],
            [
                'attribute' => 'taxLabel',
                'label' => 'НДС',
            ],
            [
                'label' => 'Цена (закупочная/отпускная)',
                'value' => function (self $model) {
                    return $model->cost_price . ' / ' . $model->price;
                },
            ],
            [
                'attribute' => 'allow_to_sell',
                'value' => function (self $model) {
                    return $model->allow_to_sell ? 'да' : 'нет';
                },
            ],
            'article_number',
            'code',
        ];
    }

    /** @inheritDoc */
    protected static function prepareData(array $data): array
    {
        if (array_key_exists('barCodes', $data) === true) {
            $data['bar_codes'] = $data['barCodes'];
            unset($data['barCodes']);
        }
        if (array_key_exists('costPrice', $data) === true) {
            $data['cost_price'] = $data['costPrice'];
            unset($data['costPrice']);
        }
        if (array_key_exists('measureName', $data) === true) {
            $data['measure_name'] = $data['measureName'];
            unset($data['measureName']);
        }
        if (array_key_exists('allowToSell', $data) === true) {
            $data['allow_to_sell'] = $data['allowToSell'];
            unset($data['allowToSell']);
        }
        if (array_key_exists('articleNumber', $data) === true) {
            $data['article_number'] = $data['articleNumber'];
            unset($data['articleNumber']);
        }
        if (array_key_exists('parentUuid', $data) === true) {
            $data['parent_id'] = $data['parentUuid'];
            unset($data['parentUuid']);
        }
        if (isset($data['parent_id']) === true && is_string($data['parent_id']) === true) {
            $data['parent_id'] = static::idByUUID($data['parent_id']);
        }
        return $data;
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

    protected function parsePath(string $path)
    {
        $i = strpos($path, '/stores/');
        if ($i === false) {
            return;
        }
        $path = substr($path, $i + 8);
        $i = strpos($path, '/');
        $path = substr($path, 0, $i);
        $this->store_id = $path;
    }

}