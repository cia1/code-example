<?php

namespace common\models\bitrix24;

use yii\db\ActiveRecord;

/**
 * Интеграция Битрикс24: связь пользователей Битрикс с клиентами КУБ
 *
 * @property int    $id
 * @property int    $company_id Идентификатор компании КУБ
 * @property string $member_id  Идентификатор пользователя Битрикс24
 */
class User extends ActiveRecord
{

    public static function tableName()
    {
        return 'bitrix24_user';
    }

    /**
     * Ищет клиента по идентификатору Битрикс24 и создаёт нового, если не находит
     *
     * @param int    $companyId Идентификатор клиента КУБ
     * @param string $memberId  Идентификатор пользователя Битрикс24
     * @return User
     */
    public static function createOrLoad(int $companyId, string $memberId): self
    {
        $user = static::findOne([
            'company_id' => $companyId,
            'member_id' => $memberId,
        ]);
        if ($user === null) {
            $user = new static();
            $user->company_id = $companyId;
            $user->member_id = $memberId;
        }
        return $user;
    }

    public static function findByMemberId(string $id)
    {
        return static::findOne(['member_id' => $id]);
    }

    public function rules()
    {
        return [
            [['company_id', 'member_id'], 'required'],
            ['company_id', 'integer'],
            ['member_id', 'string', 'length' => 32],
        ];
    }

}