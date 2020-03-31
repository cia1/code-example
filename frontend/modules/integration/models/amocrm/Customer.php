<?php

namespace frontend\modules\integration\models\amocrm;

use common\models\amocrm\Customer as CustomerCommon;
use yii\helpers\Html;
use yii\helpers\Url;

class Customer extends CustomerCommon
{
    use HookTrait;

    public function getGridColumns(): array
    {
        return [
            'id',
            'status_id',
            'name',
            'deleted' => [
                'attribute' => 'deleted',
                'value' => function (self $model) {
                    return $model->deleted ? 'да' : 'нет';
                },
            ],
            'next_price',
            'periodicity',
            [
                'attribute' => 'next_date',
                'value' => function (self $model) {
                    return date('d.m.Y H:i:s', $model->created_at);
                },
                'headerOptions' => [
                    'class' => 'sorting',
                ],
            ],
            [
                'attribute' => 'created_at',
                'value' => function (self $model) {
                    return date('d.m.Y H:i:s', $model->created_at);
                },
                'headerOptions' => [
                    'class' => 'sorting',
                ],
            ],
            [
                'attribute' => 'updated_at',
                'value' => function (self $model) {
                    return date('d.m.Y H:i:s', $model->updated_at);
                },
                'headerOptions' => [
                    'class' => 'sorting',
                ],
            ],
            [
                'header' => 'Примечания',
                'format' => 'raw',
                'value' => function (self $model) {
                    $cnt = $model->notesCount;
                    if ($cnt === 0) {
                        return 0;
                    }
                    return Html::a($model->notesCount,
                        Url::to(['/integration/amocrm/note', 'entity' => Note::ELEMENT_TYPE_CUSTOMER, 'id' => $model->id]));
                },
            ],
        ];
    }
}