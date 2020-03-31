<?php

namespace frontend\modules\integration\models\bitrix24;

use common\models\bitrix24\Section as SectionCommon;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\httpclient\Exception;

/**
 * @mixin HookTrait
 */
class Section extends SectionCommon
{
    use HookTrait;

    /**
     * @param int $id
     * @return mixed|null
     * @throws Exception
     */
    public function loadFromRest(int $id)
    {
        return $this->helper->rest('crm.productsection.get', ['ID' => $id]);
    }

    /**
     * Создаёт товарный каталог, если его ещё нет
     *
     * @inheritDoc
     */
    public function prepareRestData(array $data)
    {
        if (isset($data['catalog_id']) === true && $data['catalog_id'] !== null) {
            $catalog = Catalog::findOrCreate($this->company_id, $data['catalog_id'], $this->helper);
            $data['catalog_id'] = $catalog->id;
            unset($catalog);
        }
        $data['parent_id'] = $data['section_id'];
        unset($data['section_id']);
        if ($data['parent_id'] > 0) {
            $section = Section::findOrCreate($this->company_id, $data['parent_id'], $this->helper);
            $data['parent_id'] = $section->id;
        }
        return $data;
    }

    public function getGridColumns(): array
    {
        return [
            [
                'attribute' => 'name',
                'value' => function (self $model) {
                    if ($model->productCount === 0) {
                        $name = $model->name;
                    } else {
                        $name = Html::a($model->name, Url::to('/integration/bitrix24/' . $model->catalog_id . '/' . $model->id));
                    }
                    return str_repeat('---', $model->level) . ' ' . $name;
                },
                'format' => 'raw',
            ],
            'productCount',
        ];
    }

    public function attributeLabels()
    {
        return [
            'name' => 'Категория',
            'productCount' => 'Количество товаров',

        ];
    }
}