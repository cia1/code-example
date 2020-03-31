<?php

namespace common\models\bitrix24;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * Интеграция Битрикс24: группы (категории) товаров
 *
 * @property int       $id           Идентификатор группы (категории) Битрикс24
 * @property int       $company_id   Идентификатор компании КУБ
 * @property int       $catalog_id   Идентификатор товарного каталога
 * @property int|null  $parent_id    Идентификатор родительской группы
 * @property string    $name         Название группы
 *
 * @property Product[] $products     Товары в категории
 * @property int       $productCount Количество товаров в категории
 */
class Section extends ActiveRecord
{

    /** @var int Уровень вложенности категории (только для self::flatWithLevel) */
    public $level;

    public static function tableName()
    {
        return 'bitrix24_section';
    }

    /**
     * Возвращает дерево категорий
     *
     * @param int $companyId Идентификатор клиента КУБ
     * @param int $catalogId Идентификатор товарного каталога
     * @return array: ['model'] - Section, ['child'] - array
     */
    public static function tree(int $companyId, int $catalogId): array
    {
        $tree = [];
        foreach (static::findAll(['company_id' => $companyId, 'catalog_id' => $catalogId]) as $item) {
            if (isset($tree[$item->id]) === false) {
                $tree[$item->id] = [
                    'child' => [],
                ];
            }
            $tree[$item->id]['model'] = $item;
            if (isset($tree[$item->parent_id]) === false) {
                $tree[$item->parent_id] = [
                    'child' => [],
                ];
            }
            $tree[$item->parent_id]['child'][] =& $tree[$item->id];
        }
        return $tree['']['child'];
    }

    /**
     * Возвращает плоский список моделей, заполняя дополнительное поле $this->level (уровень вложенности)
     *
     * @param int $companyId Идентификатор клиента КУБ
     * @param int $catalogId Идентификатор торгового каталога
     * @return array
     */
    public static function flatWithLevel(int $companyId, int $catalogId): array
    {
        $flat = [];
        self::_flatTreeWithLevel(self::tree($companyId, $catalogId), 0, $flat);
        return $flat;
    }

    public function rules()
    {
        return [
            ['catalog_id', 'required'],
            [['catalog_id', 'parent_id'], 'integer'],
            ['name', 'string', 'max' => 120],
        ];
    }

    public function getProducts(): ActiveQuery
    {
        return $this->hasMany(Product::class, ['company_id' => 'company_id', 'section_id' => 'id']);
    }

    public function getProductCount(): int
    {
        return $this->getProducts()->count();
    }

    private static function _flatTreeWithLevel(array $tree, int $level, array &$flat)
    {
        foreach ($tree as $item) {
            $item['model']->level = $level;
            $flat[] = $item['model'];
            if (count($item['child']) > 0) {
                self::_flatTreeWithLevel($item['child'], $level + 1, $flat);
            }
        }
    }

}