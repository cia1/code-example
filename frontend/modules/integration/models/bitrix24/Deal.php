<?php

namespace frontend\modules\integration\models\bitrix24;

use common\models\bitrix24\Deal as DealCommon;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\httpclient\Exception;

/**
 * @mixin HookTrait
 *
 * @property string $fullContact Полное имя контакта (ФИО и компания)
 * @property string $statusLabel
 */
class Deal extends DealCommon
{
    use HookTrait;


    /**
     * @param int $id
     * @return mixed|null
     * @throws Exception
     */
    public function loadFromRest(int $id)
    {
        return $this->helper->rest('crm.deal.get', ['ID' => $id]);
    }

    /**
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function prepareRestData(array $data)
    {
        //Привязка компании
        if ($data['company_id']) {
            $tmp = Company::findOrCreate($this->company_id, $data['company_id'], $this->helper);
            $data['company_id'] = $tmp->id;
        }
        //Привязка контакта
        if ($data['contact_id']) {
            $tmp = Contact::findOrCreate($this->company_id, $data['contact_id'], $this->helper);
            $data['contact_id'] = $tmp->id;
        }
        //Тип сделки
        $data['type'] = $data['type_id'];
        if ($data['type']) {
            $tmp = $this->helper->rest('crm.status.entity.items', ['entityId' => 'DEAL_TYPE']);
            foreach ($tmp as $item) {
                if ($item['STATUS_ID'] == $data['type']) {
                    $data['type'] = $item['NAME'];
                    break;
                }
            }
        }
        //Источник
        $data['source'] = $data['source_id'];
        if ($data['source']) {
            $tmp = $this->helper->rest('crm.status.entity.items', ['entityId' => 'SOURCE']);
            foreach ($tmp as $item) {
                if ($item['STATUS_ID'] == $data['source']) {
                    $data['source'] = $item['NAME'];
                    break;
                }
            }
        }
        //Этап
        $data['stage'] = $data['stage_id'];
        if ($data['stage']) {
            $tmp = $this->helper->rest('crm.status.entity.items', ['entityId' => 'DEAL_STAGE']);
            foreach ($tmp as $item) {
                if ($item['STATUS_ID'] == $data['stage']) {
                    $data['stage'] = $item['NAME'];
                    break;
                }
            }
        }
        $data['currency'] = $data['currency_id'];
        $data['amount'] = $data['opportunity'];
        $data['tax'] = $data['tax_value'];
        $data['begin_date'] = $data['begindate'];
        $data['close_date'] = $data['closedate'];
        $data['created_at'] = $data['date_create'];
        $data['updated_at'] = $data['date_modify'];
        if ($data['opened'] === 'Y') {
            $data['status'] = Deal::STATUS_OPEN;
        }
        if ($data['closed'] === 'Y') {
            $data['status'] = Deal::STATUS_CLOSE;
        }
        unset($data['type_id'], $data['stage_id'], $data['currency_id'], $data['opportunity'], $data['tax_value'], $data['begindate'], $data['closedate'], $data['date_create'], $data['date_modify'], $data['opened'], $data['closed'], $data['source_id']);
        return $data;
    }

    /**
     * Загружает и создаёт товарные позиции сделки
     *
     * @param bool  $insert
     * @param array $changedAttributes
     * @throws Exception
     */
    public function afterSave($insert, $changedAttributes)
    {
        $position = $this->helper->rest('crm.deal.productrows.get', ['ID' => $this->id]);
        foreach ($position as $item) {
            $model = new DealPosition();
            $model->setHelper($this->helper);
            $model->company_id = $this->company_id;
            $model->id = $item['ID'];
            $model->isNewRecord = !DealPosition::find()->where(['company_id' => $this->company_id, 'id' => $item['ID']])->exists();
            $model->load($model->prepareRestData(array_change_key_case($item)), '');
            $model->save();
        }
        parent::afterSave($insert, $changedAttributes);
    }

    public function getGridColumns(): array
    {
        return [

            'title',
            ['attribute' => 'fullContact', 'format' => 'raw'],
            'type',
            'stage',
            [
                'attribute' => 'amount',
                'value' => function (self $model) {
                    $value = $model->amount . ' ' . $model->currency;
                    if ($model->tax > 0) {
                        $value .= ' / ' . $model->tax . ' ' . $model->currency;
                    }
                    return $value;
                },
            ],
            'statusLabel',
            [
                'attribute' => 'source',
                'value' => function (self $model) {
                    return $model->source . ($model->source_description ? '<br>(' . $model->source_description . ')' : '');
                },
                'format' => 'raw',
            ],
            [
                'value' => function (self $model) {
                    return ($model->begin_date ? date('d.m.Y', $model->begin_date) : '-') . ' / ' . ($model->close_date ? date('d.m.Y', $model->close_date) : '-');
                },
                'label' => 'Дата открытия / закрытия',
            ],
            [
                'attribute' => 'positionCount',
                'value' => function (self $model) {
                    return Html::a($model->positionCount, Url::to('/integration/bitrix24/deal/' . $model->id));
                },
                'format' => 'raw',
            ],
            [
                'attribute' => 'invoiceCount',
                'value' => function (self $model) {
                    return Html::a($model->invoiceCount, Url::to('/integration/bitrix24/deal/' . $model->id.'/invoice'));
                },
                'format' => 'raw',
            ],
        ];
    }

    /**
     * Возвращает Полное имя контакта (ФИО и компания)
     *
     * @return string
     */
    public function getFullContact(): string
    {
        $value = '';
        if ($this->contact_id !== null) {
            $value = $this->contact->fullName;
        }
        if ($this->bitrix24_company_id !== null) {
            if ($value !== '') {
                $value .= '<br>';
            }
            $value .= '(' . $this->company->title . ')';
        }
        return $value;

    }

    public function getStatusLabel(): string
    {
        switch ($this->status) {
            case Deal::STATUS_CLOSE:
                return 'закрыта';
        }
        return 'открыта';
    }

    public function attributeLabels()
    {
        return [
            'title' => 'Название',
            'fullContact' => 'Контрагент',
            'type' => 'Тип',
            'stage' => 'Этап',
            'amount' => 'Сумма / налог',
            'statusLabel' => 'Статус',
            'source' => 'Источник',
            'positionCount' => 'Позиции',
            'invoiceCount' => 'Счета',
        ];
    }

}