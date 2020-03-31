<?php

namespace frontend\modules\integration\models\bitrix24;

use common\models\bitrix24\Company as CompanyCommon;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\httpclient\Exception;

/**
 * @mixin HookTrait
 *
 * @property string $phoneList     Список номеров телефонов, HTML
 * @property string $emailList     Список адресов электронной почты, HTML
 * @property string $webList       Список адресов сайтов, HTML
 * @property string $IMList        Список соц. сетей, HTML
 */
class Company extends CompanyCommon
{
    use HookTrait;


    /**
     * Возвращает русскоязычный тип контакта
     *
     * @param string $type название контакта, @see self::CONTACT_TYPE
     * @return string
     */
    public static function contactTypeLabel(string $type): string
    {
        switch ($type) {
            case self::CONTACT_TYPE_WORK:
                return 'рабочий';
            case self::CONTACT_TYPE_MOBILE:
                return 'мобильный';
            case self::CONTACT_TYPE_FAX:
                return 'факс';
            case self::CONTACT_TYPE_HOME:
                return 'домашний';
            case self::CONTACT_TYPE_PAGER:
                return 'пейджер';
            case self::CONTACT_TYPE_MAILING:
                return 'для рассылки';
            case self::CONTACT_TYPE_FACEBOOK:
                return 'facebook';
            case self::CONTACT_TYPE_VK:
                return 'ВКонтакте';
            case self::CONTACT_TYPE_LIVEJOURNAL:
                return 'LiveJournal';
            case self::CONTACT_TYPE_TWITTER:
                return 'Twitter';
            case self::CONTACT_TYPE_TELEGRAM:
                return 'Telegram';
            case self::CONTACT_TYPE_SKYPE:
                return 'Skype';
            case self::CONTACT_TYPE_VIBER:
                return 'Viber';
            case self::CONTACT_TYPE_INSTAGRAM:
                return 'Instagram';
            case self::CONTACT_TYPE_BITRIX24:
                return 'Bitrix24';
            case self::CONTACT_TYPE_OPENLINE:
                return 'Онлайн-чат';
            case self::CONTACT_TYPE_IMOL:
                return 'Открытая линия';
            case self::CONTACT_TYPE_OTHER:
                return 'другой';
        }
        return $type;
    }

    /**
     * @param int $id
     * @return mixed|null
     * @throws Exception
     */
    public function loadFromRest(int $id)
    {
        return $this->helper->rest('crm.company.get', ['ID' => $id]);
    }

    /**
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function prepareRestData(array $data)
    {
        $data['type'] = $data['company_type'];
        //Тип компании
        if ($data['type']) {
            $tmp = $this->helper->rest('crm.status.entity.items', ['entityId' => 'COMPANY_TYPE']);
            foreach ($tmp as $item) {
                if ($item['STATUS_ID'] == $data['type']) {
                    $data['type'] = $item['NAME'];
                    break;
                }
            }
        }

        //Сфера деятельности
        if ($data['industry']) {
            $tmp = $this->helper->rest('crm.status.entity.items', ['entityId' => 'INDUSTRY']);
            foreach ($tmp as $item) {
                if ($item['STATUS_ID'] == $data['industry']) {
                    $data['industry'] = $item['NAME'];
                    break;
                }
            }
        }

        $data['logo'] = $data['logo']['downloadUrl'];
        $data['currency'] = $data['currency_id'];
        $data['created_at'] = $data['date_create'];
        $data['modified_at'] = $data['date_modify'];
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
        unset($data['company_type'], $data['currency_id'], $data['date_create'], $data['date_modify']);
        return $data;
    }


    public function getGridColumns(): array
    {
        return [
            'title',
            'type',
            'industry',
            [
                'attribute' => 'revenue',
                'value' => function (self $model) {
                    return number_format($model->revenue, 0, '.', ' ') . ' ' . $model->currency;
                },
            ],
            ['attribute' => 'phoneList', 'format' => 'raw'],
            ['attribute' => 'emailList', 'format' => 'raw'],
            ['attribute' => 'webList', 'format' => 'raw'],
            ['attribute' => 'IMList', 'format' => 'raw'],
            [
                'attribute' => 'invoiceCount',
                'value' => function (self $model) {
                    return Html::a($model->invoiceCount, Url::to('/integration/bitrix24/company/' . $model->id . '/invoice'));
                },
                'format' => 'raw',
            ],
        ];
    }

    public function attributeLabels()
    {
        return [
            'title' => 'Название',
            'type' => 'Тип',
            'industry' => 'Сфера деятельности',
            'revenue' => 'Годовой оборот',
            'phoneList' => 'Телефоны',
            'emailList' => 'E-mail',
            'webList' => 'Web',
            'IMList' => 'Соц. сети',
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


    private function _htmlList(string $attribute): string
    {
        $value = '<ul>';
        foreach ($this->$attribute as $item) {
            $value .= '<li>' . $item['value'] . ' (' . self::contactTypeLabel($item['type']) . ')</li>';
        }
        $value .= '</ul>';
        return $value;
    }

}