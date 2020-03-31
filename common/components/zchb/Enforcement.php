<?php

namespace common\components\zchb;

/**
 * Исполнительное производство
 *
 * @see https://zachestnyibiznesapi.ru/#api-fssp
 * @see https://zachestnyibiznesapi.ru/#api-fssp-list
 */
class Enforcement extends ZCHBIteratorContainer
{
    protected static function method(): string
    {
        return 'fssp';
    }

    protected function filterItem(array $data): array
    {
        return $data;
    }

}