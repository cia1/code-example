<?php

namespace frontend\modules\integration\models\bitrix24;

use common\models\bitrix24\Product as ProductCommon;
use Yii;
use yii\httpclient\Exception;

/**
 * @mixin HookTrait
 */
class Product extends ProductCommon
{
    use HookTrait;

    /**
     * @param int $id
     * @return mixed|null
     * @throws Exception
     */
    public function loadFromRest(int $id)
    {
        return $this->helper->rest('crm.product.get', ['ID' => $id]);
    }

    public function prepareRestData(array $data)
    {
        if (isset($data['section_id']) === true && $data['section_id'] !== null) {
            $model = Section::findOrCreate($this->company_id, $data['section_id'], $this->helper);
            $data['section_id'] = $model->id;
        }
        if (isset($data['vat_id']) === true && $data['vat_id'] !== null) {
            $model = Vat::findOrCreate($this->company_id, $data['vat_id'], $this->helper);
            $data['vat_id'] = $model->id;
        }
        $data['status'] = $data['active'] === 'Y';
        $data['picture'] = $data['preview_picture']['downloadUrl'] ?? null;
        $data['created_at'] = $data['date_create'];
        $data['currency'] = $data['currency_id'];
        unset($data['active'], $data['preview_picture'], $data['date_create'], $data['currency_id']);
        return $data;
    }

    public function getGridColumns(): array
    {
        return [
            'name',
            [
                'attribute' => 'status',
                'value' => function (self $model) {
                    return $model->status ? 'вкл.' : 'выкл.';
                },
            ],
            'vat.name',
            [
                'attribute' => 'vat_included',
                'value' => function (self $model) {
                    return $model->vat_included ? 'да' : 'нет';
                },
            ],
        ];
    }

    public function attributeLabels()
    {
        return [
            'picture' => 'Изображение',
            'name' => 'Название',
            'vat.name' => 'Налог',
            'status' => 'Статус',
            'vat_included' => 'НДС включён',
        ];
    }
}