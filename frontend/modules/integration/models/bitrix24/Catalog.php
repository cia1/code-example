<?php

namespace frontend\modules\integration\models\bitrix24;

use common\models\bitrix24\Catalog as CatalogCommon;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\httpclient\Exception;

/**
 * @mixin HookTrait
 */
class Catalog extends CatalogCommon
{
    use HookTrait;

    /**
     * @param int $id
     * @return mixed|null
     * @throws Exception
     */
    public function loadFromRest(int $id)
    {
        return $this->helper->rest('crm.catalog.get', ['ID' => $id]);
    }

    public function getGridColumns(): array
    {
        return [
            [
                'attribute' => 'name',
                'value' => function (self $model) {
                    return Html::a($model->name, Url::to('/integration/bitrix24/' . $model->id));
                },
                'format' => 'raw',
            ],
        ];
    }

    public function attributeLabels()
    {
        return [
            'name' => 'Название',
        ];
    }

}