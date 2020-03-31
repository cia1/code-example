<?php

namespace common\models\evotor;

use common\components\helpers\ArrayHelper;
use common\components\IntegrationDateBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * Интеграция Эвотор: товары и группы (категории) товаров
 *
 * @see https://developer.evotor.ru/docs/rest_products.html
 *
 * @property string            $id
 * @property int               $company_id       Идентификатор компании КУБ
 * @property int               $store_id          ID магазина (evotor_store.id)
 * @property int               $parent_id         ID родительского товара (категория)
 * @property string            $uuid              UUID товара в Эвотор (внешний ID)
 * @property bool              $group             Признак, что это группа товаров (категория), а не товар
 * @property string            $name              Название товара
 * @property string            $type              Тип товара @see self::TYPE
 * @property int               $quantity          Остаток
 * @property string            $measure_name      Единици измерения
 * @property string            $tax               Налоговая ставка, @see self::TAX
 * @property float             $price             Отпускная цена
 * @property bool              $allow_to_sell     Разрешена ли продажа товара
 * @property float|null        $cost_price        Закупочная цена
 * @property string            $description       Описание
 * @property string            $article_number    Артикул
 * @property string|null       $code              Код товара или группы
 * @property string|null       $bar_codes         Штрихкоды через запятую
 * @property string|array|null $attributes        Атрибуты товара (JSON)
 * @property int               $created_at        UNIXTIME создания примечания
 * @property int               $updated_at        UNIXTIME последнего изменения примечания
 */
class Product extends ActiveRecord
{

    //Тип товара
    const TYPE_NORMAL = 'NORMAL';
    const TYPE_ALCOHOL_MARKED = 'ALCOHOL_MARKED';
    const TYPE_ALCOHOL_NOT_MARKED = 'ALCOHOL_NOT_MARKED';
    const TYPE_TOBACCO_MARKED = 'TOBACCO_MARKED';
    const TYPE_SERVICE = 'SERVICE';
    const TYPE = [
        self::TYPE_NORMAL,
        self::TYPE_ALCOHOL_MARKED,
        self::TYPE_ALCOHOL_NOT_MARKED,
        self::TYPE_TOBACCO_MARKED,
        self::TYPE_SERVICE,
    ];

    //Налоговая ставка
    const TAX_NO_VAT = 'NO_VAT';
    const TAX_VAT_10 = 'VAT_10';
    const TAX_VAT_18 = 'VAT_18';
    const TAX_VAT_0 = 'VAT_0';
    const TAX_VAT_18_118 = 'VAT_18_118';
    const TAX_VAT_10_110 = 'VAT_10_110';
    const TAX_VAT_20 = 'VAT_20';
    const TAX_VAT_20_120 = 'VAT_20_120';
    const TAX = [
        self::TAX_NO_VAT,
        self::TAX_VAT_10,
        self::TAX_VAT_18,
        self::TAX_VAT_0,
        self::TAX_VAT_18_118,
        self::TAX_VAT_10_110,
        self::TAX_VAT_20,
        self::TAX_VAT_20_120,
    ];

    public static function tableName()
    {
        return 'evotor_product';
    }

    /**
     * Ищет только товары, группы товаров (категории) находятся в этой же таблице
     *
     * @return ActiveQuery
     */
    public static function findProduct(): ActiveQuery
    {
        return static::find()->where(['group' => 0]);
    }

    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            IntegrationDateBehavior::class,
        ]);
    }

    public function rules()
    {
        return [
            ['group', 'default', 'value' => false],
            ['type', 'default', 'value' => self::TYPE_NORMAL],
            ['tax', 'default', 'value' => self::TAX_VAT_18],
            ['allow_to_sell', 'default', 'value' => true],
            [['company_id', 'store_id', 'group', 'name', 'type', 'tax', 'allow_to_sell'], 'required'],
            [['company_id', 'store_id', 'parent_id', 'quantity'], 'integer'],
            ['uuid', 'string', 'length' => 36],
            [['group', 'allow_to_sell'], 'boolean'],
            ['name', 'string', 'max' => 100],
            ['type', 'in', 'range' => static::TYPE],
            ['measure_name', 'string', 'max' => 10],
            ['code', 'string', 'max' => 11],
            ['tax', 'in', 'range' => static::TAX],
            [['price', 'cost_price'], 'double', 'min' => 0],
            ['description', 'string', 'max' => 1000],
            ['article_number', 'string', 'max' => 20],
            [['bar_codes', 'attributes'], 'string'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'name' => 'Название',
            'type' => 'Тип товара',
            'quantity' => 'Остаток',
            'measure_name' => 'Ед. измерения',
            'tax' => 'Налоговая ставка',
            'price' => 'Отпускная цена',
            'cost_price' => 'Закупочная цена',
            'allow_to_sell' => 'В продаже',
            'article_number' => 'Артикул',
            'code' => 'Код',
        ];
    }

    public function load($data, $formName = null)
    {
        if (isset($data['bar_codes']) === true && is_array($data['bar_codes']) === true) {
            $data['bar_codes'] = implode(', ', $data['bar_codes']);
        }
        return parent::load($data, $formName);
    }

    public function setBar_codes($value)
    {
        if (is_array($value) === true) {
            $value = implode(', ', $value);
        }
        parent::__set('bar_codes', $value);

    }

}