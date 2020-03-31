<?php

namespace common\components\zchb;


use ArrayIterator;
use Iterator;

/**
 * Контейнер для списка сущностей
 */
abstract class ZCHBIteratorContainer extends ZCHBContainer implements Iterator
{

    /**
     * Метод фильтрации одного элемента сущности
     *
     * @param array $data
     * @return mixed
     */
    abstract protected function filterItem(array $data);

    public function valid()
    {
        return isset($this->data[key($this->data)]);
    }

    public function current()
    {
        return current($this->data);
    }

    public function key()
    {
        return key($this->data);
    }

    public function rewind()
    {
        return reset($this->data);
    }

    public function next()
    {
        return next($this->data);
    }

    /**
     * Количество записей о внесении изменений
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->data);
    }

    public function getIterator()
    {
        return new ArrayIterator($this->data);
    }

    protected function filter(array $data): array
    {
        $out = [];
        foreach ($data as $key => $item) {
            $item = $this->filterItem($item);
            if ($item !== null) {
                $out[$key] = $item;
            }
        }
        return $out;
    }

}