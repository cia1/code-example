<?php

namespace common\components\zchb;

use yii\base\InvalidArgumentException;

/**
 * Финансовая отчётность
 *
 * @see https://zachestnyibiznesapi.ru/#api-fs
 * @property int[] $revenue     Выручка
 * @property int[] $profit      Прибыль
 * @property int[] $tax         Налоговые отчисления
 * @property int[] $fixedAssets Основные средства
 */
class FinancialStatement extends ZCHBContainer
{

    //Финансовые показатели:
    const INDICATOR_REVENUE = 'revenue'; //выручка
    const INDICATOR_PROFIT = 'profit'; //прибыль
    const INDICATOR_TAX = 'tax'; //налоговые отчисления
    const INDICATOR_FIXED_ASSETS = 'fixedAssets'; //основные средства

    /**
     * Преобразует код строки бухгалтерского баланса в путь (строка, разделённая "->")
     *
     * @param int $code
     * @return string
     * @throws InvalidArgumentException
     */
    public static function code2Path(int $code): string
    {
        $data = self::_code2Path();
        if (isset($data[$code]) === false) {
            throw new InvalidArgumentException('Незивестный код (' . $code . ') бухгалтерского баланса');
        }
        return $data[$code];
    }

    /**
     * Преобразует путь (хлебные крошки) в код строки бухгалтерского баланса
     *
     * @param string|array $path
     * @return int
     * @throws InvalidArgumentException
     */
    public static function path2Code($path): int
    {
        $data = array_flip(self::_code2Path());
        if (isset($data[$path]) === false) {
            throw new InvalidArgumentException('Незивестный код (' . $path . ') бухгалтерского баланса');
        }
        return $data[$path];
    }

    private $_year;

    public function __construct($id = null, ZCHBHelper $helper = null)
    {
        parent::__construct($id, $helper);
        $this->_year = (int)date('Y') - 1;
    }

    public function setYear(int $year)
    {
        $this->_year = $year;
    }

    public function getYear(): int
    {
        return $this->_year;
    }

    /**
     * Возвращает отсортированный список годов, за которые есть бухгалтерская отчётность (пассив)
     *
     * @param bool             $range     Если TRUE, вернёт массив, диапазонов дат, напирмер [0]: 2014, [1]: 2016-2018, [2]: 2019
     * @param int|string|array $indicator Индикатор или массив индикаторов, по которым нужно извлечь информацию о годах
     * @param bool             $lastYear  Если TRUE, то добавит последний отчётный год, если его ещё нет в списке
     * @return array
     */
    public function yearList(bool $range = false, $indicator = 1700, bool $lastYear = true): array
    {
        if (is_array($indicator) === false) {
            $indicator = [$indicator];
        }
        $year = [];
        foreach ($indicator as $item) {
            $year = array_merge($year, array_keys($this->valueAllOf($item) ?? []));
        }
        $year = array_unique($year);
        if (empty($year) === true) {
            return $year;
        }
        //Добавить последний отчётный год, если его ещё нет в списке
        if ($lastYear === true) {
            $y = date('Y') - 1;
            if ((int)date('m') < 9) {
                $y--;
            }
            if (in_array($y, $year) === false) {
                $year[] = $y;
            }
        }

        sort($year);
        if ($range === true) {
            $year = self::_list2Range($year);
        }
        return $year;
    }

    /**
     * Переводит число в удобочитаемый вид, добавляет HTML-разметку
     *
     * @param float     $amount
     * @param bool|null $digit true - знак "+" и "-", false - нет знака, null - только "-"
     * @param string    $empty Значение, отображаемое вместо нуля
     * @return string
     */
    public static function amountFormat(float $amount, $digit = null, string $empty = '-'): string
    {
        if ($amount === 0.0) {
            return '<span class="num">' . $empty . '</span>';
        }
        $html = '';
        if ($digit !== false) {
            if ($amount < 0) {
                $html = '<span class="digit">-</span>';
                $amount = abs($amount);
            } elseif ($digit === true) {
                $html = '<span class="digit">+</span>';
            }
        } else {
            $amount = abs($amount);
        }
        if ($amount > 1000000) {
            $amount = round($amount / 1000000, 1) . ' млн';
        } elseif ($amount > 1000) {
            $amount = round($amount / 1000, 1) . ' тыс';
        } else {
            $amount = round($amount);
        }
        return $html . '<span class="num">' . $amount . '</span> ₽';
    }

    /**
     * Возвращает элемент дерева бухгалтерского баланса, находя нужный элемент по указанному "пути"
     * Если $attribute целое число, то оно воспринимается как код строки бухгалтерского баланса
     * Если $attribute - строка, то она воспринимается как "путь", разделённый символами "->"
     * Если $attribute массив, то он воспринимается как "путь" в виде списка ключей
     *
     * @param $attribute
     * @return array|null
     * @throws InvalidArgumentException
     */
    public function valueAllOf($attribute)
    {
        switch ($attribute) {
            case self::INDICATOR_REVENUE:
                $attribute = 'report->Доходы и расходы по обычным видам деятельности->Выручка';
                break;
            case self::INDICATOR_PROFIT:
                $attribute = 'report->Прочие доходы и расходы->Чистая прибыль (убыток)';
                break;
            case self::INDICATOR_TAX:
                $attribute = 'report->Прочие доходы и расходы->Текущий налог на прибыль';
                break;
            case self::INDICATOR_FIXED_ASSETS:
                $attribute = 'balance->active->I. ВНЕОБОРОТНЫЕ АКТИВЫ->Основные средства';
                break;
            default:
                if (is_int($attribute) === true) {
                    $attribute = self::code2Path($attribute);
                }
        }
        if (is_string($attribute) === true) {
            $attribute = explode('->', $attribute);
        }
        $data = $this->data;
        foreach ($attribute as $a) {
            if (isset($data[$a]) === false) {
                return null;
            }
            $data = $data[$a];
        }
        return $data;
    }

    /**
     * Если $year null, то данные будут возаращены за базовый год
     * Если $year целое число, то данные будут возвращаены за этот год
     * Если $year bool, то данные будут возвращены за последний год, за который имеются данные (true - поменять базовый год)
     *
     * @param int|string|array $attribute Показатель, по которому нужно найти требуемый элемент
     * @param bool|int|null    $year
     * @return array|mixed|null
     */
    public function valueOf($attribute, $year = null)
    {
        $data = $this->valueAllOf($attribute);
        if (is_array($data) === false || empty($data) === true) {
            return null;
        }
        if (is_bool($year) === true) {
            $y = $d = null;
            foreach ($data as $y => $d) {
                break;
            }
            if ($year === true) {
                $this->_year = $y;
            }
            return $d;
        }
        if ($year === null) {
            $year = $this->_year;
        }
        if ($year < 0) {
            $year = $this->_year + $year;
        }
        return $data[$year] ?? null;
    }

    /**
     * Максимальный год, за который есть информация по указанным показателям (по всем одновременно)
     *
     * @param int|string|array $indicator Индикатор или массив индикаторов, по которым нужно вычислить максимальный год
     * @return int
     */
    public function lastYear($indicator): int
    {
        if (is_array($indicator) === false) {
            $indicator = [$indicator];
        }
        $year = [];
        foreach ($indicator as $item) {
            $y = $this->yearList(false, $item, false);
            if (empty($y) === true) {
                return 0;
            }
            if (empty($year) === true) {
                $year = $y;
            } else {
                $year = array_intersect($year, $y);
            }
        }
        return max($year);
    }

    /**
     * Сумма выручки
     *
     * @param int $year
     * @return int
     */
    public function amountRevenue(int $year = null): int
    {
        if ($year === null) {
            return array_values($this->get(self::INDICATOR_REVENUE))[0];
        }
        if ($year < 0) {
            $year = $this->_year + $year;
        }
        return $this->get(self::INDICATOR_REVENUE)[$year] ?? 0;
    }

    /**
     * Сумма прибыли
     *
     * @param int $year
     * @return int
     */
    public function amountProfit(int $year = null): int
    {
        if ($year === null) {
            return array_values($this->get(self::INDICATOR_PROFIT))[0];
        }
        if ($year < 0) {
            $year = $this->_year + $year;
        }
        return $this->get(self::INDICATOR_PROFIT)[$year] ?? 0;
    }

    /**
     * Сумма налоговых отчислений
     *
     * @param int $year
     * @return int
     */
    public function amountTax(int $year = null): int
    {
        if ($year === null) {
            return array_values($this->get(self::INDICATOR_TAX))[0];
        }
        if ($year < 0) {
            $year = $this->_year + $year;
        }
        return $this->get(self::INDICATOR_TAX)[$year] ?? 0;
    }

    /**
     * Сумма основных средств
     *
     * @param int $year
     * @return int
     */
    public function amountFixedAssets(int $year = null): int
    {
        if ($year === null) {
            return array_values($this->get(self::INDICATOR_FIXED_ASSETS))[0];
        }
        if ($year < 0) {
            $year = $this->_year + $year;
        }
        return $this->get(self::INDICATOR_FIXED_ASSETS)[$year] ?? 0;
    }

    protected static function method(): string
    {
        return 'fs';
    }

    public function get(string $indicator): array
    {
        switch ($indicator) {
            case self::INDICATOR_REVENUE:
                return $this->data['report']['Доходы и расходы по обычным видам деятельности']['Выручка'] ?? [];
            case self::INDICATOR_PROFIT:
                return $this->data['report']['Прочие доходы и расходы']['Чистая прибыль (убыток)'] ?? [];
            case self::INDICATOR_TAX:
                return $this->data['report']['Прочие доходы и расходы']['Текущий налог на прибыль'] ?? [];
            case self::INDICATOR_FIXED_ASSETS:
                return $this->data['balance']['active']['I. ВНЕОБОРОТНЫЕ АКТИВЫ']['Основные средства'] ?? [];
        }
        return [];
    }

    protected function filter(array $data): array
    {
        $data = self::_unsetEmpty($data);
        $data = self::filterByAlias([
            'balance' => [
                'Бухгалтерский баланс',
                [
                    'active' => 'Актив',
                    'passive' => 'Пассив',
                ],
            ],
            'report' => 'Отчет о прибылях и убытках',
        ], $data);
        return $data;
    }

    private static function _unsetEmpty(array $data): array
    {
        foreach ($data as $i => $item) {
            if (is_array($item) === true) {
                $data[$i] = self::_unsetEmpty($item);
            } elseif ($item === '' || $item === null/* || $item === '0' || $item === 0*/) {
                unset($data[$i]);
            } elseif (ctype_digit($item) === true) {
                $data[$i] = (int)$item;
            }
        }
        return $data;
    }

    private static function _code2Path(): array
    {
        return [
            1100 => 'balance->active->I. ВНЕОБОРОТНЫЕ АКТИВЫ->ИТОГО по разделу I',
            1110 => 'balance->active->I. ВНЕОБОРОТНЫЕ АКТИВЫ->Нематериальные активы',
            1150 => 'balance->active->I. ВНЕОБОРОТНЫЕ АКТИВЫ->Основные средства',
            1160 => 'balance->active->I. ВНЕОБОРОТНЫЕ АКТИВЫ->Доходные вложения в материальные ценности',
            1170 => 'balance->active->I. ВНЕОБОРОТНЫЕ АКТИВЫ->Финансовые вложения',
            1180 => 'balance->active->I. ВНЕОБОРОТНЫЕ АКТИВЫ->Отложенные налоговые активы',
            1190 => 'balance->active->I. ВНЕОБОРОТНЫЕ АКТИВЫ->Прочие внеоборотные активы',
            1200 => 'balance->active->II. ОБОРОТНЫЕ АКТИВЫ->ИТОГО по разделу II',
            1210 => 'balance->active->II. ОБОРОТНЫЕ АКТИВЫ->Запасы',
            1220 => 'balance->active->II. ОБОРОТНЫЕ АКТИВЫ->Налог на добавленную стоимость по приобретенным ценностям',
            1230 => 'balance->active->II. ОБОРОТНЫЕ АКТИВЫ->Дебиторская задолженность',
            1240 => 'balance->active->II. ОБОРОТНЫЕ АКТИВЫ->Финансовые вложения (за исключением денежных эквивалентов)',
            1250 => 'balance->active->II. ОБОРОТНЫЕ АКТИВЫ->Денежные средства и денежные эквиваленты',
            1260 => 'balance->active->II. ОБОРОТНЫЕ АКТИВЫ->Прочие оборотные активы',
            1300 => 'balance->passive->III. КАПИТАЛ И РЕЗЕРВЫ->ИТОГО по разделу III',
            1310 => 'balance->passive->III. КАПИТАЛ И РЕЗЕРВЫ->Уставный капитал (складочный капитал, уставный фонд, вклады товарищей)',
            1320 => 'balance->passive->III. КАПИТАЛ И РЕЗЕРВЫ->Собственные акции, выкупленные у акционеров',
            1350 => 'balance->passive->III. КАПИТАЛ И РЕЗЕРВЫ->Добавочный капитал (без переоценки)',
            1360 => 'balance->passive->III. КАПИТАЛ И РЕЗЕРВЫ->Резервный капитал',
            1370 => 'balance->passive->III. КАПИТАЛ И РЕЗЕРВЫ->Нераспределенная прибыль (непокрытый убыток)',
            1400 => 'balance->passive->IV. ДОЛГОСРОЧНЫЕ ОБЯЗАТЕЛЬСТВА->ИТОГО по разделу IV',
            1410 => 'balance->passive->IV. ДОЛГОСРОЧНЫЕ ОБЯЗАТЕЛЬСТВА->Заемные средства',
            1420 => 'balance->passive->IV. ДОЛГОСРОЧНЫЕ ОБЯЗАТЕЛЬСТВА->Отложенные налоговые обязательства',
            1450 => 'balance->passive->IV. ДОЛГОСРОЧНЫЕ ОБЯЗАТЕЛЬСТВА->Прочие обязательства',
            1500 => 'balance->passive->V. КРАТКОСРОЧНЫЕ ОБЯЗАТЕЛЬСТВА->ИТОГО по разделу V',
            1510 => 'balance->passive->V. КРАТКОСРОЧНЫЕ ОБЯЗАТЕЛЬСТВА->Заемные средства',
            1520 => 'balance->passive->V. КРАТКОСРОЧНЫЕ ОБЯЗАТЕЛЬСТВА->Кредиторская задолженность',
            1530 => 'balance->passive->V. КРАТКОСРОЧНЫЕ ОБЯЗАТЕЛЬСТВА->Доходы будущих периодов',
            1540 => 'balance->passive->V. КРАТКОСРОЧНЫЕ ОБЯЗАТЕЛЬСТВА->Оценочные обязательства',
            1550 => 'balance->passive->V. КРАТКОСРОЧНЫЕ ОБЯЗАТЕЛЬСТВА->Прочие обязательства',
            1600 => 'balance->active->II. ОБОРОТНЫЕ АКТИВЫ->БАЛАНС',
            1700 => 'balance->passive->V. КРАТКОСРОЧНЫЕ ОБЯЗАТЕЛЬСТВА->БАЛАНС',
            2100 => 'report->Доходы и расходы по обычным видам деятельности->Валовая прибыль (убыток)',
            2110 => 'report->Доходы и расходы по обычным видам деятельности->Выручка',
            2120 => 'report->Доходы и расходы по обычным видам деятельности->Себестоимость продаж',
            2200 => 'report->Доходы и расходы по обычным видам деятельности->Прибыль (убыток) от продаж',
            2210 => 'report->Доходы и расходы по обычным видам деятельности->Коммерческие расходы',
            2220 => 'report->Доходы и расходы по обычным видам деятельности->Управленческие расходы',
            2300 => 'report->Прочие доходы и расходы->Прибыль (убыток) до налогообложения',
            2310 => 'report->Прочие доходы и расходы->Доходы от участия в других организациях',
            2320 => 'report->Прочие доходы и расходы->Проценты к получению',
            2330 => 'report->Прочие доходы и расходы->Проценты к уплате',
            2340 => 'report->Прочие доходы и расходы->Прочие доходы',
            2350 => 'report->Прочие доходы и расходы->Прочие расходы',
            2400 => 'report->Прочие доходы и расходы->Чистая прибыль (убыток)',
            2410 => 'report->Прочие доходы и расходы->Текущий налог на прибыль',
            2421 => 'report->Прочие доходы и расходы->Постоянные налоговые обязательства (активы)',
            2430 => 'report->Прочие доходы и расходы->Изменение отложенных налоговых обязательств',
            2450 => 'report->Прочие доходы и расходы->Изменение отложенных налоговых активов'
        ];
    }

    private static function _list2Range(array $yearList): array
    {
        $range = [];
        $begin = 0;
        foreach ($yearList as $i => $y) {
            if (isset($yearList[$i + 1]) === true && $yearList[$i + 1] === $y + 1) {
                if ($begin === 0) {
                    $begin = $y;
                }
                continue;
            }
            if ($begin === 0) {
                $range[] = $y;
            } else {
                if ($begin + 1 === $y) { //только два года в диапазоне
                    $range[] = $begin . ', ' . $y;
                } else {
                    $range[] = $begin . '-' . $y;
                }
                $begin = 0;
            }
        }
        return $range;
    }

}
