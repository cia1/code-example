<?php

namespace common\components\zchb;

/**
 * Рейтинг компании
 *
 * @property string      $rating_category Категория рейтинга компании
 * @property string      $risk_level      Уровень налоговых рисков при работе с Контрагентом
 * @property string|null $message         Может содержать дополнительные сведения
 */
class Rating extends ZCHBContainer
{

    protected static function method(): string
    {
        return 'rating';
    }

    public function ratingBGColor(): string
    {
        switch ($this->rating_category) {
            case 'высокий':
                return '#09c400';
            case 'средний':
            default:
                return '#9ddc92';
            case 'низкий':
                return '#c40000';
        }
    }

    protected function filter(array $data): array
    {
        return $data;
    }

}