<?php

namespace frontend\modules\integration\models\amocrm;

use common\models\amocrm\Lead as LeadCommon;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * AMOcrm: сделки. Модель для визуального отображения в Grid::widgtet()
 *
 * @property-read array gridColumns
 */
class Lead extends LeadCommon
{
    use HookTrait;

    public function getGridColumns(): array
    {
        return [
            'id',
            'status_id',
            [
                'label' => 'Контакты',
                'value' => function (self $model) {
                    $value = '<ul class="amoCustom">';
                    $contact = $model->mainContact;
                    if ($contact) {
                        $value .= '<li>' . Html::a('Контакт', Url::to('/integration/amocrm/contact')) . ' ' . $contact->name . '</li>';
                    }
                    $contact = $model->contact;
                    if ($contact) {
                        $value .= '<li>' . Html::a('Компания', Url::to('/integration/amocrm/company')) . ' ' . $contact->name . '</li>';
                    }
                    $value .= '</ul>';
                    return $value;
                },
                'format' => 'raw',
            ],
            'name',
            'price',
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
                        Url::to(['/integration/amocrm/note', 'entity' => Note::ELEMENT_TYPE_LEAD, 'id' => $model->id]));
                },
            ],
        ];
    }

    protected function loadAmoData(int $userId, $data)
    {
        if ($data['name'] == '') {
            $data['name'] = null;
        }
        $this->load($data, '');
        $this->id = $data['id'];
        $this->setCompanyId($userId);
    }

}