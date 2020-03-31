<?php

namespace frontend\modules\integration\models\amocrm;

use common\models\amocrm\Task as TaskCommon;
use yii\db\ActiveQuery;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * @property-read string|null $elementTypeLabel
 * @property-read string|null $elementTypeGridLink
 * @property-read string|null $elementTitle
 * @property Lead|null        $element
 */
class Task extends TaskCommon
{
    use HookTrait;

    public static function typeLabel(int $type)
    {
        switch ($type) {
            case self::TYPE_CALL:
                return 'Звонок';
            case self::TYPE_MEETING:
                return 'Встреча';
            case self::TYPE_LETTER:
                return 'Письмо';
            default:
                return '';
        }
    }

    /**
     * Возвращает название типа связанной сущности
     *
     * @return string|null
     */
    public function getElementTypeLabel()
    {
        switch ($this->element_type) {
            case self::ELEMENT_TYPE_CONTACT:
                return 'Контакт';
            case self::ELEMENT_TYPE_LEAD:
                return 'Сделка';
            case self::ELEMENT_TYPE_COMPANY:
                return 'Компания';
            case self::ELEMENT_TYPE_CUSTOMER:
                return 'Клиент';
        }
        return null;
    }

    /**
     * Возвращает URL страницы, содержащей таблицу с привязанной сущностью
     *
     * @return string|null
     */
    public function getElementTypeGridLink()
    {
        switch ($this->element_type) {
            case self::ELEMENT_TYPE_CONTACT:
                return Url::to('/integration/amocrm/contact');
            case self::ELEMENT_TYPE_LEAD:
                return Url::to('/integration/amocrm/lead');
            case self::ELEMENT_TYPE_COMPANY:
                return Url::to('/integration/amocrm/contact');
            case self::ELEMENT_TYPE_CUSTOMER:
                return Url::to('/integration/amocrm/customer');
        }
        return null;
    }

    /**
     * Возвращает название связанной сущности
     *
     * @param bool $link Добавить ссылку к типу сущности
     * @return string|null
     */
    public function getElementTitle(bool $link = true)
    {
        if ($this->elementTypeLabel === null || $this->element === null) {
            return null;
        }
        if ($link === true) {
            $value = Html::a($this->elementTypeLabel, $this->elementTypeGridLink);
        } else {
            $value = $this->elementTypeLabel;
        }
        return $value . ' &laquo;' . $this->element->name . '&raquo;';
    }

    /**
     * @return ActiveQuery|null
     */
    public function getElement()
    {
        if ($this->element_id === null || $this->element_type === null) {
            return null;
        }
        return $this->hasOne(Lead::class, ['id' => 'element_id']);
    }

    public function getGridColumns(): array
    {
        return [
            [
                'attribute' => 'task_type',
                'value' => function (self $model) {
                    $value = $model->elementTitle;
                    if ($value !== null) {
                        $value = '<br />' . $value;
                    } else {
                        $value = '';
                    }
                    $value = '<strong>' . self::typeLabel($model->task_type) . '</strong>' . $value;
                    return $value;
                },
                'format' => 'raw',

            ],
            'text',
            [
                'attribute' => 'status',
                'value' => function (self $model) {
                    return $model->status ? 'завершено' : 'не завершено';
                },
            ],
            [
                'attribute' => 'complete_before',
                'value' => function (self $model) {
                    return date('d.m.Y H:i:s', $model->complete_before);
                },
                'headerOptions' => [
                    'class' => 'sorting',
                ],
            ],
            [
                'attribute' => 'complete_till',
                'value' => function (self $model) {
                    return date('d.m.Y H:i:s', $model->complete_till);
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
                        Url::to(['/integration/amocrm/note', 'entity' => Note::ELEMENT_TYPE_TASK, 'id' => $model->id]));
                },
            ],
        ];
    }

    protected function loadAmoData(int $userId, $data)
    {
        if ($data['element_id'] == 0) {
            $data['element_id'] = null;
        }
        if ($data['element_type'] == 0) {
            $data['element_type'] = null;
        }
        $this->load($data, '');
        $this->id = $data['id'];
        $this->setCompanyId($userId);
    }

}