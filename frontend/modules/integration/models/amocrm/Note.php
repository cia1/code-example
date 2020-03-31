<?php

namespace frontend\modules\integration\models\amocrm;

use common\models\amocrm\Note as NoteCommon;

class Note extends NoteCommon
{
    use HookTrait;

    public static function typeLabel(int $type)
    {
        switch ($type) {
            case self::TYPE_COMMON:
                return 'примечание';
            case self::TYPE_TASK_RESULT:
                return 'результат';
            case self::TYPE_SYSTEM:
                return 'системное';
            case self::TYPE_SMS_IN;
                return 'CMC входящее';
            case self::TYPE_SMS_OUT:
                return 'СМС исходящее';
            default:
                return '';
        }
    }

    public function getGridColumns(): array
    {
        return [
            'id',
            [
                'attribute' => 'note_type',
                'value' => function (self $model) {
                    return self::typeLabel($model->note_type);
                },

            ],
            'text',
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
                'attribute' => 'updated_at',
                'value' => function (self $model) {
                    return date('d.m.Y H:i:s', $model->updated_at);
                },
                'headerOptions' => [
                    'class' => 'sorting',
                ],
            ],
        ];
    }

}