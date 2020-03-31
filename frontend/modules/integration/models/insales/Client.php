<?php

namespace frontend\modules\integration\models\insales;

use common\models\insales\Client as ClientBase;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * @property string $typeLabel
 */
class Client extends ClientBase
{

    /**
     * Создаёт или обновляет данные клиента
     *
     * @param int   $id        Идентификатор клиента InSales
     * @param int   $companyId Идентификатор клиента КУБ
     * @param array $data      Данные клиента
     * @return self|null
     */
    public static function createOrUpdate(int $id, int $companyId, array $data)
    {
        $client = self::findOne(['id' => $id]);
        if ($client === null) {
            $client = new self();
            $client->id = $id;
            $client->company_id = $companyId;
        }
        $client->load($data, '');
        if ($client->save() === false) {
            return null;
        }
        return $client;
    }

    public function getGridColumns(): array
    {
        return [
            'typeLabel',
            'email',
            'phone',
            'fullName',
            'bonus_points',
            [
                'label' => 'Скидка (накопительная/група)',
                'value' => function (self $model) {
                    return $model->progressive_discount . ' / ' . $model->group_discount;
                },
            ],
            [
                'attribute' => 'orderCount',
                'value' => function (self $model) {
                    return Html::a($model->orderCount, Url::to(['/integration/insales/order', 'client' => $model->id]));
                },
                'format' => 'raw',
            ],
            [
                'attribute' => 'shippingAddressCount',
                'value' => function (self $model) {
                    return Html::a($model->orderCount, Url::to(['/integration/insales/client/' . $model->id . '/shipping-address']));
                },
                'format' => 'raw',
            ],
        ];
    }

    public function attributeLabels()
    {
        return [
            'typeLabel' => 'Тип',
            'email' => 'E-mail',
            'phone' => 'Телефон',
            'fullName' => 'Имя',
            'bonus_points' => 'Бонус',
            'orderCount' => 'Заказы',
            'shippingAddressCount' => 'Адреса доставки',
        ];
    }

    public function getTypeLabel(): string
    {
        switch ($this->type) {
            case self::TYPE_INDIVIDUAL:
                return 'Физ. лицо';
            case self::TYPE_JURIDICAL:
                return 'Юр. лицо';
            default:
                return '';
        }
    }

}