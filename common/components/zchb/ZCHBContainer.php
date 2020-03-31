<?php

namespace common\components\zchb;

use Closure;
use Yii;

/**
 * Контейнер для сущности, получаемой из API
 */
abstract class ZCHBContainer
{

    /** @var ZCHBHelper */
    protected $helper;
    protected $data = [];
    /** @var int|null */
    protected $cacheTime;

    /**
     * Удаляет кеш по указанной компании
     * @param string $id ОГРН или ИНН
     */
    public static function clearCache($id)
    {
        ZCHBHelper::cacher()->delete(static::method() . md5(http_build_query(['id' => $id])));
    }

    /**
     * Метод для очистки и преобразования данных, полученных от API.
     * Должен исключить ненужные данные, чтобы не захломлять кеш.
     *
     * @param array $data Исходные данные
     * @return array
     * @see self::filterByAlias()
     *
     */
    abstract protected function filter(array $data): array;

    /**
     * Должен вернуть метод (точку входа) API-запроса на получение данных
     *
     * @return string
     */
    abstract protected static function method(): string;

    /**
     * TODO: В настоящее время на сервере используется библиотека INTL, не поддерживающая склонения в полной мере, убрать в случае обновления библиотеки.
     *
     * @param int         $number
     * @param string      $one
     * @param string      $few
     * @param string      $many
     * @param string|null $empty
     * @return string
     */
    public static function plural($number, string $one, string $few, string $many, string $empty = null): string
    {
        $number = (int)$number;
        if ($number === 0 && $empty !== null) {
            return $empty;
        }
        $words = [$one, $few, $many];
        return $number . ' ' . $words[($number % 100 > 4 && $number % 100 < 20) ? 2 : [
                2,
                0,
                1,
                1,
                1,
                2
            ][min($number % 10, 5)]];
    }

    public static function pluralReplace($number, string $one, string $few, string $many, string $empty = null): string
    {
        $number = (int)$number;
        if ($number === 0 && $empty !== null) {
            return $empty;
        }
        $words = [$one, $few, $many];
        return str_replace('{n}', $number,
            $words[($number % 100 > 4 && $number % 100 < 20) ? 2 : [2, 0, 1, 1, 1, 2][min($number % 10, 5)]]);

    }

    /**
     * @param string          $id Идентификатор (ОГРН/ИНН)
     * @param ZCHBHelper|null $helper
     * @return self|null
     * @throws ZCHBAPIException
     */
    public static function instance($id, ZCHBHelper $helper = null)
    {
        $self = new static(null, $helper);
        if (!$id || $self->findByID($id) === false) {
            return null;
        }
        return $self;
    }

    /**
     * @param string          $id Идентификатор (ОГРН/ИНН)
     * @param ZCHBHelper|null $helper
     * @throws ZCHBAPIException
     */
    public function __construct($id = null, ZCHBHelper $helper = null)
    {
        if ($helper === null) {
            $helper = ZCHBHelper::instance();
        }
        $this->helper = $helper;
        if ($id !== null) {
            $this->findByID($id);
        }
    }

    /**
     * @param string $id ОГРН/ИНН
     * @return bool
     * @throws ZCHBAPIException
     */
    public function findByID(string $id): bool
    {
        return $this->request(static::method(), ['id' => $id]);
    }

    /**
     * @param       $method
     * @param array $params
     * @return bool Были ли данные найдены и загружены
     * @throws ZCHBAPIException
     */
    protected function request($method, array $params): bool
    {
        $this->data = $this->helper->requestOne($method, $params, function ($data) {
            if ($data === null) {
                return [];
            }
            return $this->filter($data);
        });
        $this->cacheTime = $this->helper->cacheTime();
        if ($this->data === null) {
            return false;
        }
        return true;
    }

    /**
     * Время актуальности кеша
     *
     * @return int|null
     */
    public function cacheTime()
    {
        return $this->cacheTime;
    }

    public function __get($attribute)
    {
        return $this->data[$attribute] ?? null;
    }

    public function empty(): bool
    {
        return count($this->data) === 0;
    }

    public function getData(bool $withLabel = false): array
    {
        if ($withLabel === false) {
            return $this->data ?? [];
        }
        $data = $this->data ?? [];
        $attributeLabel = static::attributeLabels();
        foreach ($data as $key => $value) {
            $data[$key] = [
                'label' => $attributeLabel[$key] ?? $key,
                'value' => $value,
            ];
        }
        return $data;
    }

    /**
     * Фильтрует данные, оставляя только те атрибуты, что указаны в параметре $aliases
     * Предполагается вызов этого метода из static::filter()
     *
     * @param array $aliases Псевдонимы и имена полей исходного массива данных, где ключ - новое имя атрибута, значение - имя атрибута, полученное через API
     *                       Если ключ не задан, то атрибут не будет переименован.
     *                       Значением может быть строка (имя атрибута), функция (должна возвращать значение для атрибута)
     *                       или массив, где первый элемент - имя атрибута, второй - функция или массив вложенных атрибутов
     *
     * @param       $data
     * @return array
     */
    protected static function filterByAlias(array $aliases, array $data)
    {
        $out = [];
        foreach ($aliases as $alias => $attribute) {
            if (is_int($alias) === true) {
                $alias = $attribute;
            }
            if ($attribute instanceof Closure) {
                $value = $attribute($data);
                if ($value !== null) {
                    $out[$alias] = $value;
                }
            } elseif (is_array($attribute) === true) {
                $subData = $data[$attribute[0]] ?? null;
                if ($subData === null || is_array($subData) === false) {
                    continue;
                }
                if (isset($subData[0]) === true) {
                    $tmp = [];
                    foreach ($subData as $i => $item) {
                        if (is_callable($attribute[1]) === true) {
                            $tmp[$i] = $attribute[1]($item);
                        } else {
                            $tmp[$i] = self::filterByAlias($attribute[1], $item);
                        }
                    }
                } else {
                    if (is_callable($attribute[1]) === true) {
                        $tmp = $attribute[1]($subData);
                    } else {
                        $tmp = self::filterByAlias($attribute[1], $subData);
                    }
                }
                $out[$alias] = $tmp;
            } elseif (isset($data[$attribute]) === true) {
                $out[$alias] = $data[$attribute];
            }
        }
        return $out;
    }

    /**
     * Возвращает заголовок (название) атрибута
     *
     * @param string $attribute
     * @return string
     * @see static::attributeLabels()
     */
    protected static function attributeLabel(string $attribute): string
    {
        return static::attributeLabels()[$attribute] ?? $attribute;
    }

    /**
     * Массив заголовков (названий) атрибутов
     * Предполагается перегрузка этого метода в дочерних классах.
     *
     * @return array Формат: ключ - имя атрибута, значение - заголовок
     */
    protected static function attributeLabels(): array
    {
        return [];
    }

}