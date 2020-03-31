<?php

namespace frontend\modules\integration\models\bitrix24;

use common\models\bitrix24\Invoice as InvoiceCommon;
use yii\helpers\Html;
use yii\httpclient\Exception;

/**
 * @mixin HookTrait
 */
class Invoice extends InvoiceCommon
{
    use HookTrait;


    /**
     * @param int $id
     * @return mixed|null
     * @throws Exception
     */
    public function loadFromRest(int $id)
    {
        return $this->helper->rest('crm.invoice.get', ['ID' => $id]);
    }

    public function prepareRestData(array $data)
    {
        //Статус
        $data['status'] = $data['status_id'];
        if ($data['status']) {
            $tmp = $this->helper->rest('crm.status.entity.items', ['entityId' => 'INVOICE_STATUS']);
            foreach ($tmp as $item) {
                if ($item['STATUS_ID'] == $data['status']) {
                    $data['status'] = $item['NAME'];
                    break;
                }
            }
        }
        $data['deal_id'] = $data['uf_deal_id'];
        $data['company_id'] = $data['uf_company_id'];
        $data['contact_id'] = $data['uf_contact_id'];
        $data['number'] = $data['account_number'];
        $data['amount'] = $data['price'];
        $data['tax'] = $data['tax_value'];
        $data['comment'] = $data['user_description'];
        $data['comment_manager'] = $data['comments'];
        $data['date_paid'] = $data['date_payed'];
        $data['created_at'] = $data['date_insert'];
        $data['updated_at'] = $data['date_update'];
        unset($data['status_id'], $data['uf_deal_id'], $data['uf_company_id'], $data['uf_contact_id'], $data['account_number'], $data['price'], $data['tax_value'], $data['user_description'], $data['comments'], $data['date_payed'], $data['date_insert'], $data['date_update']);
        return $data;
    }


    public function getGridColumns(): array
    {
        return [
            'status',
            'number',
            'date_paid',
            [
                'attribute' => 'amount',
                'value' => function (self $model) {
                    return $model->amount . ' ' . $model->currency . ' / ' . $model->tax . ' ' . $model->currency;
                },
            ],
            'deal.title',
            ['attribute' => 'fullContact', 'format' => 'raw'],
            [
                'attribute' => 'comment',
                'value' => function (self $model) {
                    $value = $model->comment;
                    if ($model->comment_manager) {
                        if ($value) {
                            $value .= '<br><b>Менеджер</b>: ' . $model->comment_manager;
                        }
                    }
                    return $value;
                },
                'format' => 'raw',
            ],
        ];
    }

    public function getFullContact(): string
    {
        $value = $this->contact->fullName;
        if ($this->bitrix24_company_id) {
            $value .= '<br>(' . $this->company->title . ')';
        }
        return $value;
    }

    public function attributeLabels()
    {
        return [
            'status' => 'Статус',
            'date_paid' => 'Оплата',
            'deal.title' => 'Сделка',
            'fullContact' => 'Контакт (компания)',
            'number' => 'Номер',
            'amount' => 'Сумма / Налог',
            'comment' => 'Комментарий',
        ];
    }

}