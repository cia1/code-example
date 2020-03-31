<?php

namespace common\components\zchb;

use Yii;
use yii\base\InvalidConfigException;

/**
 * Исторические изменения сведений об организации
 *
 * @see https://zachestnyibiznesapi.ru/#api-diffs
 */
class Diff extends ZCHBIteratorContainer
{

    /*
     * Список атрибутов, поддерживаемых классом.
     * Ключ - имя атрибута в исходных данных, полученных через API
     * Значение[0] - отображаемое название заголовка, значение[1] - имя метода, преобразующего данные в удобочитаемый вид
     */
    const ATTRIBUTE = [
        'ДатаВып' => ['Дата выписки', 'fnsDate'],
        'СвЛицензия' => ['Сведения о лицензиях, выданных юридическому лицу', 'license'],
        'СвАдресЮЛ' => ['Юридический адрес', 'address'],
        'СвЗапЕГРЮЛ' => ['Сведения ЕГРЮЛ', 'egrul'],
        'ГРНДата' => ['ГРН / дата записи', 'grnDate'],
    ];

    protected static function method(): string
    {
        return 'diffs';
    }

    /**
     * Количество записей о внесении изменений
     *
     * @param string $attribute Имя атрибута
     * @return int
     */
    public function count(string $attribute = null): int
    {
        if ($attribute === null) {
            return parent::count();
        }
        $cnt = 0;
        foreach ($this->data as $item) {
            if ($item['attribute'] === $attribute) {
                $cnt++;
            }
        }
        return $cnt;
    }

    /**
     * @return string
     * @throws InvalidConfigException
     */
    public function lastDate(): string
    {
        $tmp = array_keys($this->data);
        return Yii::$app->formatter->asDate($tmp[count($tmp) - 1], 'long');
    }

    protected function filterItem(array $itemList)
    {
        $outItem = [];
        foreach ($itemList as $item) {
            $item = $item['data'];
            $attribute = $item['node'] === '@attributes' ? array_keys($item['diff']['upd'])[0] : $item['node'];
            $s = self::_attributeLabel($attribute ?? '');
            $tmp = self::_attributeFormatter($attribute, $item['diff']['upd']);
            if ($tmp !== '') {
                $s .= ': ' . $tmp;
            }
            $outItem[] = [
                'attribute' => $attribute,
                'value' => $s,
            ];
        }
        return $outItem;
    }

    private static function _attributeLabel(string $attribute): string
    {
        if (ctype_digit($attribute) === true) {
            return '';
        }
        $attribute = self::ATTRIBUTE[$attribute] ?? $attribute;
        if (is_array($attribute) === true) {
            $attribute = $attribute[0];
        }
        return $attribute;
    }

    private static function _attributeFormatter($attribute, $item): string
    {
        if ($attribute === null || isset(self::ATTRIBUTE[$attribute]) === false || is_array(self::ATTRIBUTE[$attribute]) === false) {
            return '';
        }
        $formatter = self::ATTRIBUTE[$attribute][1];
        return self::$formatter($item);
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private static function fnsDate(array $data): string
    {
        $date = $data['ДатаВып']['ins'];
        return substr($date, -2) . '.' . substr($date, 5, 2) . '.' . substr($date, 0, 4);
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private static function license(array $data): string
    {
        $s = '';
        foreach ($data as $key => $item) {
            $s .= '<strong>' . $key . '</strong>: ';
            if (isset($item['del']) === true) {
                $s .= '<span class="del">№' . $item['del']['@attributes']['НомЛиц'] . ' (с ' . $item['del']['@attributes']['ДатаНачЛиц'] . ' по ' . $item['del']['@attributes']['ДатаНачЛиц'] . ')</span>';
            }
            if (isset($item['ins']) === true) {
                $s .= '<span class="ins">№' . $item['ins']['@attributes']['НомЛиц'] . ' (с ' . $item['ins']['@attributes']['ДатаНачЛиц'] . ' по ' . $item['ins']['@attributes']['ДатаНачЛиц'] . ')</span>';
            }
        }
        return $s;
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private static function address(array $data): string
    {
        $s = '';
        foreach ($data as $item) {
            foreach ($item as $class => $subItem) {
                $s .= '<span class="' . $class . '">';
                foreach ($subItem as $changes) {
                    $i = 0;
                    foreach ($changes as $key => $tmp) {
                        if ($key === '@attributes') {
                            continue;
                        }
                        if ($i++ > 0) {
                            $s .= ', ';
                        }
                        $s .= self::_attributeLabel($key) . ': ' . implode(' ', $tmp['@attributes']);
                    }
                }
                $s .= '</span>';
            }
        }
        return $s;
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private static function egrul(array $data): string
    {
        $s = '';
        foreach ($data as $item) {
            if ($s) {
                $s .= ', ';
            }
            foreach ($item as $class => $changes) {
                $s .= '<span class="' . $class . '">';
                $s .= $changes['ВидЗап']['@attributes']['НаимВидЗап'];
                $s .= '</span>';
            }
        }
        return $s;
    }

}