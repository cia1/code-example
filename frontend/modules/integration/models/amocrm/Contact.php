<?php

namespace frontend\modules\integration\models\amocrm;

use common\models\amocrm\Contact as ContactCommon;
use yii\helpers\Html;
use yii\helpers\Url;

class Contact extends ContactCommon
{
    use HookTrait;

    public function getGridColumns(): array
    {
        return [
            'id',
            'name',
            [
                'attribute' => 'created_at',
                'format' => 'raw',
                'value' => function (self $model) {
                    return date('d.m.Y H:i:s', $model->created_at);
                },
                'headerOptions' => [
                    'class' => 'sorting',
                ],
            ],
            [
                'label' => 'Контакты',
                'value' => function (self $model) {
                    $value = '<ul class="amoCustom">';
                    foreach ($model->custom_fields as $item) {
                        if (isset($item['code']) === false) {
                            continue;
                        }
                        $value .= '<li>';
                        $value .= $item['name'] . ': ';
                        foreach ($item['values'] as $i => $val) {
                            if ($i > 0) {
                                $value .= ', ';
                            }
                            $value .= $val['value'];
                        }
                        $value .= '</li>';
                    }
                    $value .= '</ul>';
                    return $value;
                },
                'format' => 'raw',
            ],
            [
                'label' => 'Доп. информация',
                'value' => function (self $model) {
                    $value = '<ul class="amoCustom">';
                    foreach ($model->custom_fields as $item) {
                        if (isset($item['code']) === true) {
                            continue;
                        }
                        $value .= '<li>';
                        $value .= $item['name'] . ': ';
                        foreach ($item['values'] as $i => $val) {
                            if ($i > 0) {
                                $value .= ', ';
                            }
                            $value .= $val['value'];
                        }
                        $value .= '</li>';
                    }
                    $value .= '</ul>';
                    return $value;
                },
                'format' => 'raw',
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
                'attribute' => 'linked_company_id',
                'value' => function (self $model) {
                    return $model->linked_company_id ?? '';
                },
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
                        Url::to(['/integration/amocrm/note', 'entity' => $model->type == self::TYPE_CONTACT ? Note::ELEMENT_TYPE_CONTACT : Note::ELEMENT_TYPE_COMPANY, 'id' => $model->id]));
                },

            ],
        ];
    }

}