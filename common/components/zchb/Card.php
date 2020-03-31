<?php

namespace common\components\zchb;

use Yii;
use yii\base\InvalidConfigException;

/**
 * Карточка контрагента. Этот класс должен создаваться в ZCHBHelper.
 * По сути "обёртка" для данных, возвращаемых через API, предоставляющая более удобныйинтерфейс.
 *
 *
 * @see https://zachestnyibiznesapi.ru/#api-card - описание полей
 *
 * @property array[]  $arrears                  Сумма недоимок
 * @property array[]  $directors
 * @property int      $employeeCountYear
 * @property array    $founders                 Учредители
 * @property bool     $isIndividual             Является ли юр. лицо ИП
 * @property int      $license                  Количество лицензий, выданных организации
 * @property string   $OKVEDTitle               Наименование вида деятельности по ОКВЭД
 * @property array    $reportingIndicatorsLast
 * @property string   Адрес                     Адрес (полностью)
 * @property string   ДатаОГРН                  Дата присвоения ОГРН (дд.мм.гггг)
 * @property string   ДатаПостУч                Дата постановки на учёт в налоговый орган (дд.мм.гггг)
 * @property int      ИНН
 * @property int      Индекс                    Адрес: индекс
 * @property string   КодОКВЭД                  Код по ОКВЭД
 * @property int      КодОПФ                    Код ОПФ
 * @property int      КПП
 * @property string   НаимГород                 Адрес: название города
 * @property string   НаимНО                    Наименование налогового органа, в котором юридическое лицо состоит
 * @property string   НаимЮЛПолн                Полное наименование юридического лица
 * @property int      ОГРН
 * @property int      ОКАТО
 * @property int      ОКОГУ
 * @property int      ОКПО
 * @property int      ОКТМО
 * @property string   Описание                  Сводная справка об организации
 * @property string   ПолнНаимОПФ               Полное наименование организационно-правовой формы
 * @property int      Проверки                  Количество проверок
 * @property int      Реестр01                  Находится ли в реестре "Имеется взыскиваемая судебными приставами задолженность по уплате налогов, превышающая 1000 рублей"
 * @property int      СвФилиал                  Количество филиалов организации
 * @property int|null СредЗП                    Среднемесячная заработная плата в организации
 * @property int      СумКап                    Сумма уставного капитала
 * @property string   ТипГород                  Адрес: тип города
 * @property array    УплачСтрахВзнос           Уплаченные страховые взносы за последний период по данным ФНС
 * @property int|null ФондОплТруда              Фонд оплаты труда
 * @property int      ЧислСотруд                Среднесписочная численность сотрудников за последний период по данным ФНС
 * @property array    $tax                      Информация по налоговым и страховым отчислениям за последний год
 */
class Card extends ZCHBContainer
{

    const TAX_DISABILITY = 'disability'; //мед. страхование нетрудоспособности
    const TAX_DISABILITY_TITLE = 'Взносы на соц. страхование';
    const TAX_STS = 'sts'; //УСН
    const TAX_STS_TITLE = 'Налог, взимаемый в связи с  применением УСН';
    const TAX_MEDIC = 'medic'; //мед. страхование
    const TAX_MEDIC_TITLE = 'Страховые взносы на ОМС';
    const TAX_PENSION = 'pension'; //пенсионное страхование
    const TAX_PENSION_TITLE = 'Страховые и другие взносы в ПФР';
    const TAX_PROFIT = 'profit'; //налог на прибыль
    const TAX_PROFIT_TITLE = 'Налог на прибыль';
    const TAX_VAT = 'vat'; //НДС
    const TAX_VAT_TITLE = 'Налог на добавленную стоимость';
    const TAX_ESTATE = 'estate';
    const TAX_ESTATE_TITLE = 'Налог на имущество';
    const TAX_NO_TAX = 'no_tax';
    const TAX_NO_TAX_TITLE = 'НЕНАЛОГОВЫЕ ДОХОДЫ, администрируемые налоговыми органами';

    protected static function method(): string
    {
        return 'card';
    }


    /**
     * Сумма задолженностей
     * @return float[]:
     *  float $amount Сумма
     *  float $peni   Пени
     *  float $fine   Штраф
     */
    public function arrearsTotal(): array
    {
        $amount = $peni = $fine = 0;
        foreach ($this->arrears as $item) {
            $amount += $item['amount'];
            $peni += $item['peni'];
            $fine += $item['fine'];
        }
        return [
            'amount' => $amount,
            'peni' => $peni,
            'fine' => $fine
        ];
    }

    /**
     * Действует ли организация в настоящее время
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return isset($this->data['Активность']) === true && $this->data['Активность'] === 'Действующее';
    }

    /**
     * Дата ОГРН в удобочитаемом формате
     *
     * @return string
     * @throws InvalidConfigException
     */
    public function dateOGRN(): string
    {
        return Yii::$app->formatter->asDate($this->ДатаОГРН, 'long');
    }

    public function schemaAddress(): string
    {
        return str_replace($this->Индекс, '<span itemprop="postalCode">' . $this->Индекс . '</span>', $this->Адрес);
    }

    /**
     * Должность главного (первого в списке) руководителя
     *
     * @return string|null
     */
    public function firstDirectorPosition()
    {
        return $this->data['directors'][0]['post'] ?? null;
    }

    /**
     * Имя главного (первого в списке) руководителя
     *
     * @return string|null
     */
    public function firstDirectorName()
    {
        return $this->data['directors'][0]['fl'] ?? null;
    }

    /**
     * ИНН главного (первого в списке) руководителя
     *
     * @return string|null
     */
    public function firstDirectorINN()
    {
        return $this->data['directors'][0]['inn'] ?? null;
    }

    /**
     * Дата главного (первого в списке) руководителя
     *
     * @return int|null TIMESTAMP
     */
    public function firstDirectorDate()
    {
        $date = $this->data['directors'][0]['date'] ?? null;
        return $date;
    }

    public function employeeCountFormatted(): string
    {
        return self::plural($this->ЧислСотруд, 'сотрудник', 'сотрудника', 'сотрудников', '(нет информации)');
    }

    /**
     * @return string
     */
    public function specialTaxRegime(): string
    {
        return $this->data['НалогРежим'] ?? '-нет-';
    }

    public function categoryMSP()
    {
        return $this->data['КатСубМСП'][1] ?? null;
    }

    /**
     * Количество видов деятельности согласно классификатора ОКВЭД
     *
     * @return int
     */
    public function OKVEDCount(): int
    {
        $cnt = count($this->data['СвОКВЭДДоп']);
        if ($this->КодОКВЭД) {
            $cnt++;
        }
        return $cnt;
    }

    /**
     * Дата постановления на учет в налоговый орган
     *
     * @return string
     * @throws InvalidConfigException
     */
    public function taxServiceDate(): string
    {
        return Yii::$app->formatter->asDate($this->data['ДатаПостУч'], 'long');
    }

    /**
     * Количество учредителей
     *
     * @return int
     */
    public function founderCount(): int
    {
        return count($this->founders);
    }

    protected function filter(array $data): array
    {
        $data = static::filterByAlias([
            'arrears' => [
                'СуммНедоимЗадолж',
                function ($item) {
                    if (empty($item) === true) {
                        return [];
                    }
                    return [
                        'title' => $item['НаимНалог'] ?? '',
                        'amount' => (float)str_replace(',', '.', $item['СумНедНалог'] ?? 0),
                        'peni' => (float)str_replace(',', '.', $item['СумПени'] ?? 0),
                        'fine' => (float)str_replace(',', '.', $item['СумШтраф'] ?? 0)
                    ];
                }
            ],
            'directors' => [
                'Руководители',
                [
                    'post',
                    'fl',
                    'inn',
                    'date' => function ($data) {
                        return strtotime($data['date']);
                    },
                ],
            ],
            'employeeCountYear' => function ($data) {
                $year = $data['ЧислСотрудИст'][0]['Год'] ?? null;
                if ($year === null) {
                    return (int)date('Y');
                }
                return (int)substr($year, -4);
            },
            'founders' => function ($data) {
                $founder = [];
                foreach ($data['СвУчредит']['all'] ?? [] as $item) {
                    $founder[] = [
                        'name' => $item['name'],
                        'isFL' => $item['type'] === 'fl',
                        'ogrn' => $item['ogrn'],
                        'inn' => $item['inn'],
                        'dol_abs' => $item['dol_abs'],
                        'date' => strtotime($item['date']) ?? null,
                    ];
                }
                return $founder;
            },
            'isIndividual' => function ($data) {
                return isset($data['ОГРН']) === false && isset($data['ИННФЛ']) === true;
            },
            'license' => 'СвЛицензия',
            'OKVEDTitle' => function ($data) {
                return mb_strtoupper(mb_substr($data['НаимОКВЭД'], 0, 1, 'UTF-8'),
                        'UTF-8') . mb_substr($data['НаимОКВЭД'], 1, null, 'UTF-8');
            },
            'reportingIndicatorsLast' => function ($data) {
                $data = $data['ОсновПоказОтчетнИст'] ?? null;
                if ($data === null || empty($data) === true) {
                    return null;
                }
                $data = $data[count($data) - 1];
                return [
                    'year' => $data['Год'],
                    'income' => $data['СумДоход'],
                    'outcome' => $data['СумРасход'],
                ];
            },
            'Адрес',
            'Активность',
            'ДатаОГРН',
            'ДатаПостУч',
            'ИНН' => function ($data) {
                return $data['ИНН'] ?? $data['ИННФЛ'] ?? null;
            },
            'Индекс',
            'КатСубМСП',
            'КодОКВЭД',
            'КодОПФ',
            'КПП',
            'НалогРежим',
            'НаимГород',
            'НаимНО',
            'НаимЮЛПолн',
            'ОГРН',
            'ОКАТО',
            'ОКОГУ',
            'ОКПО',
            'ОКТМО',
            'Описание',
            'ПолнНаимОПФ',
            'Проверки',
            'РегНомПФ',
            'РегНомФСС',
            'Реестр01',
            'СвОКВЭДДоп' => function ($data) {
                return $data['СвОКВЭДДоп'] ?? [];
            },
            'СвФилиал',
            'СредЗП',
            'СумКап',
            'ТипГород',
            'tax' => function ($data) {
                $out = [
                    'date' => null,
                    self::TAX_DISABILITY => 0,
                    self::TAX_STS => 0,
                    self::TAX_MEDIC => 0,
                    self::TAX_PENSION => 0,
                    self::TAX_PROFIT => 0,
                    self::TAX_VAT => 0,
                    self::TAX_ESTATE => 0,
                    self::TAX_NO_TAX => 0,

                ];
                if (isset($data['УплачСтрахВзнос']) === false) {
                    return $out;
                }
                $data = $data['УплачСтрахВзнос'];
                if (isset($data['@attributes']) === false) {
                    return $out;
                }
                $out['date'] = $data['@attributes']['ДатаСост'];
                foreach ($data['СвУплСумНал'] as $item) {
                    //Данные почему-то могут приходить в разных форматах
                    $taxName = $item['@attributes']['НаимНалог'] ?? $item['НаимНалог'];
                    $amount = (float)($item['@attributes']['СумУплНал'] ?? $item['СумУплНал']);
                    switch ($taxName) {
                        case 'Страховые взносы на обязательное социальное страхование на случай временной нетрудоспособности и в связи с материнством':
                            $out[self::TAX_DISABILITY] += $amount;
                            break;
                        case 'Налог, взимаемый в связи с  применением упрощенной  системы налогообложения':
                            $out[self::TAX_STS] += $amount;
                            break;
                        case 'Страховые взносы на обязательное медицинское страхование работающего населения, зачисляемые в бюджет Федерального фонда обязательного медицинского страхования':
                            $out[self::TAX_MEDIC] += $amount;
                            break;
                        case 'Страховые и другие взносы на обязательное пенсионное страхование, зачисляемые в Пенсионный фонд Российской Федерации':
                            $out[self::TAX_PENSION] += $amount;
                            break;
                        case self::TAX_PROFIT_TITLE:
                            $out[self::TAX_PROFIT] += $amount;
                            break;
                        case self::TAX_VAT_TITLE:
                            $out[self::TAX_VAT] += $amount;
                            break;
                        case 'Налог на имущество организаций':
                            $out[self::TAX_ESTATE] += $amount;
                            break;
                        case 'Неналоговые доходы, администрируемые налоговыми органами':
                            $out[self::TAX_NO_TAX] += $amount;
                            break;
                    }
                }
                return $out;
            },
            'ФондОплТруда',
            'ЧислСотруд',
        ], $data);
        if ($data['directors'] === null) {
            $data['directors'] = [];
        }
        return $data;
    }

}
