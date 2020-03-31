<?php

namespace frontend\modules\integration\models\bitrix24;

use common\models\bitrix24\Contact as ContactCommon;
use yii\base\Exception as ExceptionBase;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\httpclient\Exception as ExceptionClient;

/**
 * @mixin HookTrait
 */
class Contact extends ContactCommon
{
    use HookTrait;


    /**
     * @param int $id
     * @return mixed|null
     * @throws ExceptionBase
     */
    public function loadFromRest(int $id)
    {
        return $this->helper->rest('crm.contact.get', ['ID' => $id]);
    }

    /**
     * @param array $data
     * @return array
     * @throws ExceptionClient
     */
    public function prepareRestData(array $data)
    {
        $data['type'] = $data['type_id'];
        //Тип контакта
        if ($data['type']) {
            $tmp = $this->helper->rest('crm.status.entity.items', ['entityId' => 'CONTACT_TYPE']);
            foreach ($tmp as $item) {
                if ($item['STATUS_ID'] == $data['type']) {
                    $data['type'] = $item['NAME'];
                    break;
                }
            }
        }

        $data['source'] = $data['source_id'];
        //Источник
        if ($data['source']) {
            $tmp = $this->helper->rest('crm.status.entity.items', ['entityId' => 'SOURCE']);
            foreach ($tmp as $item) {
                if ($item['STATUS_ID'] == $data['source']) {
                    $data['source'] = $item['NAME'];
                    break;
                }
            }
        }

        //Обращение
        if ($data['honorific']) {
            $tmp = $this->helper->rest('crm.status.entity.items', ['entityId' => 'HONORIFIC']);
            foreach ($tmp as $item) {
                if ($item['STATUS_ID'] == $data['honorific']) {
                    $data['honorific'] = $item['NAME'];
                    break;
                }
            }
        }

        $data['comment'] = $data['comments'];
        if ($data['phone']) {
            foreach ($data['phone'] as $i => $item) {
                $data['phone'][$i] = [
                    'type' => $item['VALUE_TYPE'],
                    'value' => $item['VALUE'],
                ];
            }
        }
        if ($data['email']) {
            foreach ($data['email'] as $i => $item) {
                $data['email'][$i] = [
                    'type' => $item['VALUE_TYPE'],
                    'value' => $item['VALUE'],
                ];
            }
        }
        if ($data['web']) {
            foreach ($data['web'] as $i => $item) {
                $data['web'][$i] = [
                    'type' => $item['VALUE_TYPE'],
                    'value' => $item['VALUE'],
                ];
            }
        }
        if ($data['im']) {
            foreach ($data['im'] as $i => $item) {
                $data['im'][$i] = [
                    'type' => $item['VALUE_TYPE'],
                    'value' => $item['VALUE'],
                ];
            }
        }
        unset($data['type_id'], $data['comments'], $data['source_id']);
        if (isset($data['company_id']) === true && $data['company_id'] !== null) {
            $model = Company::findOrCreate($this->company_id, $data['company_id'], $this->helper);
            $data['section_id'] = $model->id;
        }

        return $data;
    }


    public function getGridColumns(): array
    {
        return [
            [
                'attribute' => 'fullName',
                'value' => function (self $model) {
                    $value = $model->fullName;
                    if ($model->bitrix24_company_id !== null) {
                        $value .= '<br>(' . $model->company->title . ')';
                    }
                    return $value;
                },
                'format' => 'raw',
            ],
            'type',
            'post',
            [
                'attribute' => 'source',
                'value' => function (self $model) {
                    $value = $model->source;
                    $value = mb_strtoupper(mb_substr($value, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($value, 1, null, 'UTF-8');
                    if ($model->source_description) {
                        $value .= '<br /><i>' . $model->source_description . '</i>';
                    }
                    return $value;
                },
                'format' => 'raw',
            ],
            'birthdate',

            ['attribute' => 'phoneList', 'format' => 'raw'],
            ['attribute' => 'emailList', 'format' => 'raw'],
            ['attribute' => 'webList', 'format' => 'raw'],
            ['attribute' => 'IMList', 'format' => 'raw'],
            'comment',
            [
                'attribute' => 'invoiceCount',
                'value' => function (self $model) {
                    return Html::a($model->invoiceCount, Url::to('/integration/bitrix24/contact/' . $model->id . '/invoice'));
                },
                'format' => 'raw',
            ],
        ];
    }

    public function attributeLabels()
    {
        return [
            'fullName' => 'Имя',
            'type' => 'Тип',
            'post' => 'Должность',
            'birthdate' => 'Дата рождения',
            'source' => 'Источник',
            'phoneList' => 'Телефоны',
            'emailList' => 'E-mail',
            'webList' => 'Web',
            'IMList' => 'Соц. сети',
            'comment' => 'Замечания',
            'invoiceCount' => 'Счета',
        ];
    }

    public function getPhoneList(): string
    {
        return $this->_htmlList('phone');
    }

    public function getEmailList(): string
    {
        return $this->_htmlList('email');
    }

    public function getWebList(): string
    {
        return $this->_htmlList('web');
    }

    public function getIMList(): string
    {
        return $this->_htmlList('im');
    }

    public function afterFind()
    {
        parent::afterFind();
        $this->birthdate = date('d.m.Y', $this->birthdate);
    }


    private function _htmlList(string $attribute): string
    {
        $value = '<ul>';
        foreach ($this->$attribute as $item) {
            $value .= '<li>' . $item['value'] . ' (' . Company::contactTypeLabel($item['type']) . ')</li>';
        }
        $value .= '</ul>';
        return $value;
    }
}