<?php

namespace common\models\evotor;

use yii\db\ActiveRecord;

/**
 * Интеграция Эвотор: учётные записи Эвотор
 * Таблица используется для связи клиентов сайта с клиентами Эвотор при обращении к REST API.
 *
 * @see docs/integrations.md
 *
 * @property string      $id                    Идентификатор пользователя Эвотор
 * @property int         $company_id            Идентификатор компании КУБ
 * @property string|null $token                 Токен доступа к REST API Эвотора
 * @property int         $product_time          UNIXTIME последней загрузки товаров
 * @property int         $product_load_attempts Количество неудачных попыток загрузки номенклатуры
 */
class User extends ActiveRecord
{

    /**
     * Возвращает первого пользователя, у которого данные о номенклатуре устарели
     *
     * @param int $period Интервал в секундах, в течении которого номенклатура считается актуальной
     * @return static|null
     */
    public static function notActualProducts(int $period)
    {
        /** @var static|null $user */
        $user = static::find()->where(['<', 'product_time', time() - $period])->orderBy(['product_load_attempts' => SORT_ASC, 'product_time' => SORT_ASC])->limit(1)->one();
        return $user;
    }

    public static function tableName()
    {
        return 'evotor_user';
    }

    public function rules()
    {
        return [
            [['company_id'], 'required'],
            [['company_id', 'product_time'], 'integer'],
            ['token', 'string', 'length' => 36],
        ];
    }

}