<?php

namespace common\components\zchb;

use common\models\dossier\NalogRuCard;
use Yii;
use yii\base\InvalidConfigException;
use yii\web\NotFoundHttpException;

/**
 * Помощник построения отчёта по оценке надёжности организации на основе бухгалтерской отчётности, сводной информации и судебных разбирательств.
 * Каждый фактор должен иметь строку в self::INDICATORS, функцию {factor-name}($value): array и функцию {factor-name}Value().
 * где {factor-name} - имя фактора, записанное как ключ в self::INDICATORS.
 * {factor-name}Value() - должна возвращать абсолютное значение фактора
 * {factor-name)(mixed $value): array - должна возвращать массив [group,value], содержащий информацию в пригодном для публикации на сайте виде
 */
class ReliabilityHelper
{

    //Группы факторов
    const GROUP = [
        self::GROUP_POSITIVE,
        self::GROUP_ATTENTION,
        self::GROUP_NEGATIVE,
    ];
    const GROUP_POSITIVE = 1; //позитивный фактор
    const GROUP_ATTENTION = 2; //фактор, требующий внимания
    const GROUP_NEGATIVE = 3; //негативный фактор
    //Название и описание всех показателей, ключ - имя функции
    const INDICATORS = [
        'profitDynamic' => [
            'title' => 'Динамика доходов/активов',
            'description' => 'Наблюдается отрицательная динамика доходов (по выручке) и/или активов по итогам последнего доступного периода. Рекомендуется выяснить причину снижения показателей и провести дополнительный анализ бухгалтерской отчетности контрагента.',
        ],
        'debtor' => [
            'title' => 'Зависимость от дебиторов',
            'description' => [
                self::GROUP_POSITIVE => 'Доля дебиторской задолженности в активах составляет менее 50%, что говорит о положительной платёжеспособности организации.',
                self::GROUP_ATTENTION => 'Доля дебиторской задолженности в активах составляет более 50%, что в большинстве случаев отрицательно сказывается на платежеспособности организации.',
            ],
        ],
        'ROE' => [
            'title' => 'Рентабельность собственного капитала (ROE)',
            'description' => 'Для оценки эффективности инвестиций широко используется нормативное значение ROE. Хорошим показателем считается значение превышающее 10%. Важно отметить, что слишком большое значение показателя может негативно влиять на финансовую устойчивость предприятия. Если показатель меньше 10% - это уже тревожный сигнал и стимул для того, чтобы нарастить доходность собственного капитала.',
        ],
        'capital' => [
            'title' => 'Уставный капитал',
            'description' => [
                null => 'Сведений об уставным капитале нет.',
                self::GROUP_POSITIVE => 'Уставный капитал более 100 тысяч рублей свидетельствует о стабильности организации',
                self::GROUP_ATTENTION => 'Уставный капитал менее 100 тысяч рублей, это типично для большинства средних организаций.',
            ],
        ],
        'court' => [
            'title' => 'Судебные дела',
            'description' => [
                self::GROUP_POSITIVE => 'Отсутствие судебных дел в качестве ответчика свидетельствует о надлежащем исполнении обязательств перед контрагентами.',
                self::GROUP_ATTENTION => 'Перед началом сотрудничества с контрагентом рекомендуется произвести анализ судебных дел с участием организации.',
                self::GROUP_NEGATIVE => 'Наличие судебных дел в качестве ответчика может свидетельствовать о ненадлежащем исполнении обязательств перед контрагентами',
            ],
        ],
        'form' => [
            'title' => 'Организационно-правовая форма',
            'description' => 'Данные организации изначально обладают средним уровнем доверия в силу непубличности и распространенности.',
        ],
        'SME' => [
            'title' => 'Реестр субъектов МСП',
            'description' => [
                self::GROUP_POSITIVE => 'Статус малого предприятия не присвоен',
                self::GROUP_ATTENTION => 'Организации присвоен статус малого предприятия. Статус может быть изменен после сдачи отчетности в налоговую инспекцию.',
            ],
        ],
        'taxMode' => [
            'title' => 'Налоговый спецрежим',
            'description' => 'Организации, применяющие специальные налоговые режимы, часто не являются плательщиками НДС.',
        ],
        'liquidityAbsolute' => [
            'title' => 'Коэффициент абсолютной ликвидности',
            'description' => 'Нормальное значение коэффициента - от 0,2 до 0,5. Это означает, что от 20 до 50% краткосрочных долгов организация способна погасить в кратчайшие сроки по первому требованию кредиторов. Превышение величины 0,5 указывает на неоправданные задержки в использовании высоколиквидных активов.',
        ],
        'capitalRatio' => [
            'title' => 'Коэффициент соотношения заемного и собственного капитала',
            'description' => [
                self::GROUP_POSITIVE => 'Величина коэффициента соотношения заемного и собственного капитала, находящегося в коридоре от 0,5 до 0,7, считается оптимальной и говорит об устойчивости состояния, отсутствии зависимости в финансовом плане и нормальном функционировании.',
                '>1' => 'Коэффициент больше 1 (что свидетельствует о преобладании заемных средств над собственными) служит признаком наличия риска банкротства.',
                '0.7-1' => 'Значение в пределах от 0,7 до 1 указывает на неустойчивость финансового положения и существование признаков неплатежеспособности.',
                '<0.5' => 'Значение коэффициента менее 0,5, будучи показателем устойчивого финансового положения, одновременно указывает на неэффективность работы организации.',
            ],
        ],
        'registration' => [
            'title' => 'Дата регистрации',
            'description' => [
                self::GROUP_POSITIVE => 'Организация создана более трех лет назад, что говорит о стабильной деятельности и поднадзорности государственным органам.',
                self::GROUP_ATTENTION => 'Организация создана менее трёх лет назад, что может говорить о нестабильной деятельности и поднадзорности государственным органом.',
            ],
        ],
        'director' => [
            'title' => 'Руководитель',
            'description' => [
                self::GROUP_POSITIVE => 'У организации нет изменений в данных о руководителе, что свидетельствует о стабильности аппарата принятия решений контрагента.',
                self::GROUP_ATTENTION => 'Данных об измененинии руководства нет',
                self::GROUP_NEGATIVE => 'Изменение данных о руководстве свидетельствует о возможной нестабильности в работе контрагента',
            ],
        ],
        'address' => [
            'title' => 'Юридический адрес',
            'description' => [
                self::GROUP_POSITIVE => 'Отсутствие изменений в юридическом адресе на протяжении нескольких лет косвенно свидетельствует о стабильном функционировании организации.',
                self::GROUP_ATTENTION => 'Изменение юридического адреса организации может быть следствием нестабильности организации.',
            ],
        ],
        'employeeCount' => [
            'title' => 'Среднесписочная численность',
            'description' => 'Рекомендуется соотнести масштаб деятельности организации с количеством ее сотрудников.',
        ],
        'tax' => [
            'title' => 'Налоговая нагрузка',
            'description' => [
                null => 'Информации по налоговым отчислениям нет',
                self::GROUP_POSITIVE => 'По итогам последнего доступного периода наблюдается положительная налоговая нагрузка. Это снижает налоговые риски при взаимодействии с контрагентом.',
                self::GROUP_ATTENTION => 'По итогам последнего доступного периода наблюдается отрицательная налоговая нагрузка. Это косвенно говорит о повышении налоговых рисков при взаимодействии с контрагентом.',
            ],
        ],
        'salary' => [
            'title' => 'Наличие выплат персоналу',
            'description' => [
                self::GROUP_POSITIVE => 'Наличие данных о выплатах персоналу свидетельствует о ведении реальной деятельности организацией.',
                self::GROUP_ATTENTION => 'Отсутствие данных о выплатах персоналу может свидетельствовать о ведении теневой деятельности организацией.',
            ],
        ],
        'massLeaders' => [
            'title' => 'Реестр массовых руководителей',
            'description' => [
                self::GROUP_POSITIVE => 'Руководитель организации не значится в реестре массовых руководителей ФНС.',
                self::GROUP_ATTENTION => 'Руководитель организации не определён',
                self::GROUP_NEGATIVE => 'Руководитель организации значится в реестре массовых руководителей ФНС',
            ],
        ],
        'massFounders' => [
            'title' => 'Реестр массовых учредителей',
            'description' => [
                self::GROUP_POSITIVE => 'Учредители организации не значатся в реестре массовых учредителей ФНС.',
                self::GROUP_ATTENTION => 'Нет информации об учредителях',
                self::GROUP_NEGATIVE => 'Учредитель организации значится в реестре массовых учредителей ФНС',
            ],
        ],
        'massAddress' => [
            'title' => 'Реестр массовых адресов',
            'description' => [
                self::GROUP_POSITIVE => 'Адрес регистрации организации не состоит в реестре массовых адресов регистрации ФНС.',
                self::GROUP_NEGATIVE => 'По адресу организации зарегистрировано несколько юридических лиц'
            ]
        ],
        //'disqualified' => [
        //    'title' => 'Дисквалифицированные лица',
        //    'description' => 'В состав исполнительных органов организации не входят дисквалифицированные лица.',
        //],
        'founder' => [
            'title' => 'Учредители',
            'description' => [
                self::GROUP_POSITIVE => 'У организации нет изменений в данных об учредителях за последний год. Это свидетельствует о стабильности структуры капитала организации.',
                self::GROUP_ATTENTION => 'У организации есть изменения в данных об учредителях за последний год. Это может свидетельствовать о нестабильности структуры капитала организации.',
            ],
        ],
        'status' => [
            'title' => 'Статус организации',
            'description' => [
                self::GROUP_POSITIVE => 'Информации о прекращении деятельности нет.',
                self::GROUP_NEGATIVE => 'Деятельность организации прекращена.',
            ],
        ],
        'capitallabor' => [
            'title' => 'Фондовооруженность',
            'description' => [
                self::GROUP_POSITIVE => 'Положительная фондовооруженность (остаточная стоимость собственных основных средств) по итогам последнего доступного периода снижает риски в работе с организацией.',
                self::GROUP_ATTENTION => 'Нулевая фондовооруженность (остаточная стоимость собственных основных средств) по итогам последнего доступного периода повышает риски в работе с организацией.',
            ],
        ],
        'lender' => [
            'title' => 'Зависимость от кредиторов',
            'description' => 'Рекомендуемое значение данного коэффициента должно быть меньше 0,8. Оптимальным является коэффициент 0.5 (т.е. равное соотношение собственного и заемного капитала). При значении показателя меньше 0,8 говорит о том, что обязательства должны занимать менее 80% в структуре капитала.',
        ],
        'ownFounds' => [
            'title' => 'Обеспеченность собственными средствами',
            'description' => 'Нормативное значение коэффициента обеспеченности собственными средствами составляет 10% и выше. Если на конец отчетного периода коэффициент имеет значение менее 10%, структура баланса организации признается неудовлетворительной.',
        ],
        'liquidityCurrent' => [
            'title' => 'Коэффициент текущей ликвидности',
            'description' => 'Чем выше значение коэффициента текущей ликвидности, тем выше ликвидность активов организации. Нормальным, а часто и оптимальным, считается значение коэффициента 1,7 и более.',
        ],
        'ROA' => [
            'title' => 'Рентабельность по активам',
            'description' => 'Рентабельность активов - отношение чистой прибыли (убытка) к совокупным активам, нормальным считается любое положительное значение.',
        ],
        'ROS' => [
            'title' => 'Рентабельность по продажам',
            'description' => 'Рентабельность продаж – отношение чистой прибыли (убытка) к себестоимости продаж. Нормальным считается любое положительное значение.',
        ],
        'financialAutonomy' => [
            'title' => 'Коэффициент финансовой автономии',
            'description' => 'Уровень автономии - доля оборотных средств, обеспеченных собственными средствами организации. Характеризует финансовую устойчивость организации (финансирование текущих операций за счет собственных средств), нормальным считается значение более 10%, пограничным – от 5 до 10%, критическим – менее 5%.',
        ],
        'financialStability' => [
            'title' => 'Коэффициент финансовой устойчивости',
            'description' => [
                null => 'Нет сведений о финансовой устойчивости',
                self::GROUP_POSITIVE => 'Коэффициент финансовой устойчивости находится в пределах от 0,75 до 0,95, что говорит о стабильной хозяйственной деятельности и высокой финансовой независимости организации.',
                self::GROUP_ATTENTION => 'Коэффициент финансовой устойчивости ниже 0,75 свидетельствует о высокой финансовой зависимости контрагента.',
            ],
        ],
    ];

    /** @var Card */
    public $card;
    /** @var FinancialStatement */
    public $fs;
    /** @var CourtArbitration */
    public $ca;
    /** @var Diff */
    public $diff;
    /** @var Manager */
    private $_manager;

    /**
     * @param string  $cardId
     * @param Manager $manager
     * @throws NotFoundHttpException
     * @throws ZCHBAPIException
     */
    public function __construct(string $cardId, Manager $manager)
    {
        $this->card = $manager->cardInstance($cardId);
        $this->fs = $manager->financialStatementNew($this->card->ОГРН ?? $this->card->ИНН);
        $this->ca = $manager->courtArbitrationNew($this->card->ОГРН ?? $this->card->ИНН);
        $this->diff = $manager->diffNew($this->card->ИНН);
        $this->_manager = $manager;
    }

    public function reliability(): int
    {
        $cnt = [];
        foreach (self::GROUP as $item) {
            $cnt[$item] = $this->count($item);
        }
        $max = max($cnt);
        return array_search($max, $cnt);
    }

    public function reliabilityString($isNewTheme = false): string
    {
        if ($isNewTheme) {
            switch ($this->reliability()) {
                case self::GROUP_POSITIVE:
                    return 'Высокий уровень надежности';
                case self::GROUP_ATTENTION:
                    return 'Средний уровень надежности';
                case self::GROUP_NEGATIVE:
                    return 'Низкий уровень надежности';
            }
        }

        switch ($this->reliability()) {
            case self::GROUP_POSITIVE:
                return 'высокий';
            case self::GROUP_ATTENTION:
                return 'средний';
            case self::GROUP_NEGATIVE:
                return 'низкий';
        }

        return '';
    }

    public function color($isNewTheme = false)
    {
        if ($isNewTheme) {
            switch ($this->reliability()) {
                case self::GROUP_POSITIVE:
                    return '#00c0bb';
                case self::GROUP_ATTENTION:
                default:
                    return '#dca52a';
                case self::GROUP_NEGATIVE:
                    return '#c40000';
            }
        }

        switch ($this->reliability()) {
            case self::GROUP_POSITIVE:
                return '#09c400';
            case self::GROUP_ATTENTION:
            default:
                return '#9ddc92';
            case self::GROUP_NEGATIVE:
                return '#c40000';
        }
    }

    /**
     * Количество показателей
     * Если $group не указан, возвращает общее количество
     *
     * @param int|null $group Группа показателей, @see self::ФЕЕ*
     * @return int
     */
    public function count(int $group = null)
    {
        $cnt = 0;
        foreach (self::INDICATORS as $function => $info) {
            $value = $function . 'Value';
            if (method_exists($this, $value) === false) {
                continue;
            }
            $value = $this->$value();
            if ($value === null) {
                continue;
            }
            $item = $this->$function($value);
            if ($item !== null && $group === null || $item['group'] === $group) {
                $cnt++;
            }
        }
        return $cnt;

    }

    /**
     * Количество позитивных факторов
     *
     * @return int
     */
    public function positiveCount(): int
    {
        return $this->count(self::GROUP_POSITIVE);
    }

    /**
     * Количество факторов, требующих внимания
     *
     * @return int
     */
    public function attentionCount(): int
    {
        return $this->count(self::GROUP_ATTENTION);
    }

    /**
     * Количество негативных факторов
     *
     * @return int
     */
    public function negativeCount(): int
    {
        return $this->count(self::GROUP_NEGATIVE);
    }

    /**
     * Информация по показателям
     * Если $group не указан, возвращает все показатели
     *
     * @param int|null $group Группа показателей, @see self::GROUP
     * @return array [title], [group], [value], [description]
     */
    public function indicators(int $group = null): array
    {
        $out = [];
        foreach (self::INDICATORS as $function => $info) {
            $value = $function . 'Value';
            if (method_exists($this, $value) === false) {
                $item = [
                    'group' => self::GROUP_ATTENTION,
                    'value' => '-',
                ];
            } else {
                $value = $this->$value();
                if ($value === null) {
                    continue;
                }
                $item = $this->$function($value);
                if ($item['value'] === null) {
                    $item['value'] = '-';
                }
            }
            if ($item === null || $group !== null && $item['group'] !== $group) {
                continue;
            }
            $item = array_merge($info, $item);
            if (is_array($item['description']) === true) {
                $item['description'] = $item['description'][$item['group']] ?? '';
            }
            $out[] = $item;
        }
        return $out;
    }

    /**
     * Список позитивных факторов
     *
     * @return array [title],[description], [group], [value]
     */
    public function positive(): array
    {
        return $this->indicators(self::GROUP_POSITIVE);
    }

    /**
     * Список негативных факторов
     *
     * @return array [title],[description], [group], [value]
     */
    public function negative(): array
    {
        return $this->indicators(self::GROUP_NEGATIVE);
    }

    /**
     * Список факторов, требующих внимания
     *
     * @return array [title],[description], [group], [value]
     */
    public function attention(): array
    {
        return $this->indicators(self::GROUP_ATTENTION);
    }

    /**
     * Динамика доходов/активов (только значение)
     *
     * @return bool TRUE - возрастающая, FALSE - спадающая
     */
    public function profitDynamicValue(): bool
    {
        $year = $this->fs->lastYear($this->fs::INDICATOR_FIXED_ASSETS);
        return $this->fs->amountProfit($year) - $this->fs->amountProfit($year - 1) > 0;
    }

    /**
     * Динамика доходов/активов
     *
     * @param mixed @value
     * @return array
     */
    public function profitDynamic($value): array
    {
        return [
            'group' => $value === true ? self::GROUP_POSITIVE : self::GROUP_NEGATIVE,
            'value' => $value === true ? 'Положительная динамика' : 'Отрицательная динамика',
        ];
    }

    /**
     * Зависимость от дебиторов
     *
     * @return float|null Отношение (процент) актива к дебиторской задолженности
     */
    public function debtorValue()
    {
        $all = (int)$this->fs->valueOf('balance->active->II. ОБОРОТНЫЕ АКТИВЫ->БАЛАНС');
        if ($all === 0) {
            return null;
        }
        $deb = (int)$this->fs->valueOf('balance->active->II. ОБОРОТНЫЕ АКТИВЫ->Дебиторская задолженность');
        return round($deb / $all * 100, 2);
    }

    /**
     * Зависимость от дебиторов
     *
     * @param $value
     * @return array
     */
    public function debtor($value): array
    {
        return [
            'group' => $value > 50 ? self::GROUP_ATTENTION : self::GROUP_POSITIVE,
            'value' => $value . '%',
        ];
    }

    /**
     * Рентабельность собственного капитала (ROE)
     *
     * @return float|null Значение показателя ROE
     */
    public function ROEValue()
    {
        $t = (int)$this->fs->valueOf('balance->passive->V. КРАТКОСРОЧНЫЕ ОБЯЗАТЕЛЬСТВА->БАЛАНС') + (int)$this->fs->valueOf('balance->passive->IV. ДОЛГОСРОЧНЫЕ ОБЯЗАТЕЛЬСТВА->ИТОГО по разделу IV');
        if ($t === 0) {
            return null;
        }
        $actives = (int)$this->fs->valueOf('balance->active->II. ОБОРОТНЫЕ АКТИВЫ->БАЛАНС');
        return round(($actives - $t) / $actives * 100, 2);
    }

    /**
     * Рентабельность собственного капитала (ROE)
     *
     * @param float $value
     * @return array
     */
    public function ROE(float $value): array
    {
        if ($value < 10) {
            $group = self::GROUP_NEGATIVE;
        } elseif ($value > 50) {
            $group = self::GROUP_ATTENTION;
        } else {
            $group = self::GROUP_POSITIVE;
        }
        if ($value > 0) {
            $value .= '%';
        } else {
            $value = null;
        }
        return [
            'group' => $group,
            'value' => $value,
        ];
    }

    /**
     * Уставный капитал
     *
     * @return int Сумма уставного капитала
     */
    public function capitalValue(): int
    {
        return (int)$this->card->СумКап;
    }

    /**
     * Уставный капитал
     *
     * @param int $value
     * @return array
     */
    public function capital(int $value): array
    {
        if ($value === 0) {
            return [
                'group' => self::GROUP_ATTENTION,
                'value' => null,
                'description' => self::INDICATORS['capital']['description'][null],
            ];
        }
        return [
            'group' => $value < 100000 ? self::GROUP_ATTENTION : self::GROUP_POSITIVE,
            'value' => $this->fs::amountFormat($value),
        ];
    }

    /**
     * Судебные дела
     *
     * @return int Количество дел, где орназинация выступает в роли ответчика
     */
    public function courtValue(): int
    {
        return $this->ca->countDefendant();
    }

    /**
     * Судебные дела
     *
     * @param int $value
     * @return array
     */
    public function court(int $value): array
    {
        return [
            'group' => $value === 0 ? self::GROUP_POSITIVE : self::GROUP_ATTENTION,
            'value' => $this->ca::plural($value, 'дело', 'дела', 'дел', 'Не участвовала') . ' в качестве ответчика',
        ];
    }

    /**
     * Организационно-правовая форма
     *
     * @return int|null Код ОПФ
     */
    public function formValue()
    {
        return $this->card->КодОПФ;
    }

    /**
     * Организационно-правовая форма
     * Сомнительный показатель, но, кажется, должен работать именно так
     *
     * @param int $value
     * @return array
     */
    public function form(int $value)
    {
        return [
            'group' => $value === 12300 ? self::GROUP_ATTENTION : self::GROUP_POSITIVE,
            'value' => $this->card->ПолнНаимОПФ,
        ];
    }

    /**
     * Реестр субъектов МСП
     *
     * @return string|null Категория МСП
     */
    public function SMEValue()
    {
        return $this->card->categoryMSP();

    }

    /**
     * Реестр субъектов МСП
     *
     * @param string|null $value
     * @return array
     */
    public function SME($value): array
    {
        return [
            'group' => $value === null ? self::GROUP_POSITIVE : self::GROUP_ATTENTION,
            'value' => $value,
        ];
    }

    /**
     * Налоговый спецрежим
     *
     * @return string Название налогового спецрежима
     */
    public function taxModeValue(): string
    {
        return $this->card->specialTaxRegime();
    }

    /**
     * Налоговый спецрежим
     *
     * @param string $value
     * @return array
     */
    public function taxMode(string $value): array
    {
        return [
            'group' => $value === '- нет -' ? self::GROUP_POSITIVE : self::GROUP_ATTENTION,
            'value' => $value,
        ];
    }

    /**
     * Коэффициент абсолютной ликвидности
     *
     * @return float|null
     */
    public function liquidityAbsoluteValue()
    {
        $fs = $this->fs;
        $bottom = (int)$fs->valueOf(1500) - (int)$fs->valueOf(1530) - (int)$fs->valueOf(1540);
        if ($bottom === 0) {
            return null;
        }
        return round(((int)$fs->valueOf(1240) + (int)$fs->valueOf(1250)) / $bottom, 2);
    }

    /**
     * Коэффициент абсолютной ликвидности
     *
     * @param float|null $value
     * @return array
     */
    public function liquidityAbsolute($value): array
    {
        return [
            'group' => $value >= 0.2 && $value <= 0.5 ? self::GROUP_POSITIVE : self::GROUP_ATTENTION,
            'value' => $value,
        ];
    }

    /**
     * Коэффициент соотношения заемного и собственного капитала
     *
     * @return float|null
     */
    public function capitalRatioValue()
    {
        $fs = $this->fs;
        $line1300 = $fs->valueOf(1300);
        if ($line1300 == 0) {
            return null;
        }
        return round(((int)$fs->valueOf(1410) + (int)$fs->valueOf(1510)) / $line1300, 2);
    }

    /**
     * Коэффициент соотношения заемного и собственного капитала
     *
     * @param float|null $value
     * @return array
     */
    public function capitalRatio($value): array
    {
        $out = [
            'group' => $value >= 0.7 && $value <= 0.7 ? self::GROUP_POSITIVE : self::GROUP_ATTENTION,
            'value' => $value,
        ];
        if ($value > 1) {
            $out['description'] = self::INDICATORS['capitalRatio']['description']['>1'];
        } elseif ($value > 0.7) {
            $out['description'] = self::INDICATORS['capitalRatio']['description']['0.7-1'];
        } elseif ($value < 0.5) {
            $out['description'] = self::INDICATORS['capitalRatio']['description']['<0.5'];
        }
        return $out;
    }

    /**
     * Дата регистрации
     *
     * @return int TIMESTAMP даты регистрации
     */
    public function registrationValue(): int
    {
        return strtotime($this->card->ДатаПостУч);
    }

    /**
     * Дата регистрации
     *
     * @param int $value
     * @return array
     * @throws InvalidConfigException
     */
    public function registration(int $value): array
    {
        return [
            'group' => time() - $value > 86400 * 365 ? self::GROUP_POSITIVE : self::GROUP_ATTENTION,
            'value' => Yii::$app->formatter->asDate($value, 'long'),
        ];
    }

    /**
     * Руководитель
     *
     * @return bool|int|null null - нет информации, false - не менялся, int - дата действия
     */
    public function directorValue()
    {
        $directorDate = $this->card->firstDirectorDate();
        if ($directorDate === null || $this->card->ДатаПостУч === null) {
            return null;
        }
        $period = $directorDate - strtotime($this->card->ДатаПостУч);
        if ($period > 86400 * 90) {
            return $directorDate;
        }
        return false;
    }

    /**
     * Руководитель
     *
     * @param int|bool|null $value
     * @return array
     * @throws InvalidConfigException
     */
    public function director($value): array
    {
        if ($value === false) {
            $value = 'Не менялся';
            $group = self::GROUP_POSITIVE;
        } else {
            $value = 'действует с ' . Yii::$app->formatter->asDate($value, 'long');
            $group = self::GROUP_NEGATIVE;
        }
        return [
            'group' => $group,
            'value' => $value,
        ];
    }

    /**
     * Юридический адрес
     *
     * @return bool true - адрес менялся, false - не менялся или информации нет
     */
    public function addressValue(): bool
    {
        return $this->diff->count('address') > 0;
    }

    /**
     * Юридический адрес
     *
     * @param bool $value
     * @return array
     */
    public function address(bool $value): array
    {
        return [
            'group' => $value === false ? self::GROUP_POSITIVE : self::GROUP_ATTENTION,
            'value' => $value === false ? 'Не менялся' : 'Изменился',
        ];
    }

    /**
     * Среднесписочная численность
     *
     * @return int
     */
    public function employeeCountValue(): int
    {
        return (int)$this->card->ЧислСотруд;
    }

    /**
     * Среднесписочная численность
     *
     * @param int $value
     * @return array
     */
    public function employeeCount(/** @noinspection PhpUnusedParameterInspection */ int $value): array
    {
        return [
            'group' => $value > 1000 ? self::GROUP_POSITIVE : self::GROUP_ATTENTION,
            'value' => $this->card->employeeCountFormatted(),
        ];

    }

    /**
     * Налоговая нагрузка
     *
     * @return bool|null Больше (true) или меньше (false) выплачено налогов за последний год
     */
    public function taxValue()
    {
        $tax = array_values($this->fs->valueAllOf('report->Прочие доходы и расходы->Текущий налог на прибыль') ?? []);
        if (count($tax) < 2) {
            return null;
        }
        return $tax[0] > $tax[1];
    }

    /**
     * Налоговая нагрузка
     *
     * @param bool|null $value
     * @return array
     */
    public function tax($value): array
    {
        return [
            'group' => $value > 0 ? self::GROUP_POSITIVE : self::GROUP_ATTENTION,
            'value' => $value === true ? 'Положительная' : 'Отрицательная',
        ];
    }

    /**
     * Наличие выплат персоналу
     *
     * @return float Сумма страховых выплат из фонда оплаты труда
     */
    public function salaryValue(): float
    {
        return $this->card->ФондОплТруда + $this->card->СредЗП;
        //$amountTotal = 0;
        //foreach ($this->card->УплачСтрахВзнос['СвУплСумНал'] ?? [] as $item) {
        //    $name = $item['@attributes']['НаимНалог'] ?? null;
        //    $amount = (float)$item['@attributes']['СумУплНал'] ?? 0;
        //    switch ($name) {
        //        case 'Страховые взносы на обязательное социальное страхование на случай временной нетрудоспособности и в связи с материнством':
        //        case 'Страховые взносы на обязательное медицинское страхование работающего населения, зачисляемые в бюджет Федерального фонда обязательного медицинского страхования':
        //        case 'Страховые и другие взносы на обязательное пенсионное страхование, зачисляемые в Пенсионный фонд Российской Федерации':
        //    }
        //    $amountTotal += $amount;
        //}
        //return $amountTotal;
    }

    /**
     * Наличие выплат персоналу
     *
     * @param float $value
     * @return array
     */
    public function salary(float $value): array
    {
        return [
            'group' => $value > 0 ? self::GROUP_POSITIVE : self::GROUP_ATTENTION,
            'value' => $value === 0 ? null : $this->fs::amountFormat($value),
        ];
    }

    /**
     * Реестр массовых руководителей
     *
     * @return int|null Количество действующих организаций, которыми руководит главный руководитель организации
     * @throws ZCHBAPIException
     */
    public function massLeadersValue()
    {
        $inn = $this->card->firstDirectorINN();
        if ($inn === null) {
            return null;
        }
        $flCard = $this->_manager->flCardInstance($inn);
        if ($flCard === null) {
            return null;
        }
        /** @var FlCard $flCard */
        $cnt = 0;
        foreach ($flCard->director as $item) {
            if ($item['Активность'] === 'Действующее') {
                $cnt++;
            }
        }
        return $cnt;
    }

    /**
     * Реестр массовых руководителей
     *
     * @param int $value
     * @return array
     */
    public function massLeaders(int $value): array
    {
        return [
            'group' => $value > 1 ? self::GROUP_NEGATIVE : ($value === 1 ? self::GROUP_POSITIVE : self::GROUP_ATTENTION),
            'value' => $value === 0 ? null : $this->card::plural($value, 'организация', 'организации', 'организаций',
                'нет'),
        ];
    }

    /**
     * Реестр массовых учредителей
     *
     * @return int|null Количество действующих организаций, в которых руководитель данной организации является учредителем
     * @throws ZCHBAPIException
     */
    public function massFoundersValue()
    {
        $inn = $this->card->firstDirectorINN();
        if ($inn === null) {
            return null;
        }
        $flCard = $this->_manager->flCardInstance($inn);
        if ($flCard === null) {
            return null;
        }
        /** @var FlCard $flCard */
        $cnt = 0;
        foreach ($flCard->founder as $item) {
            if ($item['Активность'] === 'Действующее') {
                $cnt++;
            }
        }
        return $cnt;
    }

    /**
     * Реестр массовых учредителей
     *
     * @param int $value
     * @return array
     */
    public function massFounders(int $value): array
    {
        return [
            'group' => $value > 1 ? self::GROUP_NEGATIVE : ($value === 1 ? self::GROUP_POSITIVE : self::GROUP_ATTENTION),
            'value' => $value === 0 ? null : $this->card::plural($value, 'организация', 'организации', 'организаций',
                'нет'),
        ];
    }

    /**
     * Реестр массовых адресов
     * @return int|null
     * @throws InvalidConfigException
     */
    public function massAddressValue()
    {
        $nalogRuCard = NalogRuCard::findByINN($this->card->ИНН);
        if ($nalogRuCard === null) {
            return null;
        }
        if ($nalogRuCard->status_address = NalogRuCard::STATUS_SUCCESS) {
            return (int)$nalogRuCard->getRelatedAddress()->count();
        }
        return null;
    }

    public function massAddress($value): array
    {
        return [
            'group' => $value < 2 ? self::GROUP_POSITIVE : self::GROUP_NEGATIVE,
            'value' => $this->ca::plural($value, 'юр. лицо', 'юр. лица', 'юр. лиц', null),
        ];
    }

    /**
     * Учредители
     * Очевидно, что это работает плохо, но другой информации нет
     *
     * @return int Количество учредителей, образованных в течении последнего года
     */
    public function founderValue(): int
    {
        $y = time() - 86400 * 365;
        $cnt = 0;
        foreach ($this->card->founders as $item) {
            if ($item['date'] > $y) {
                $cnt++;
            }
        }
        return $cnt;
    }

    /**
     * Учредители
     *
     * @param int $value
     * @return array
     */
    public function founder(int $value): array
    {
        return [
            'group' => $value > 0 ? self::GROUP_ATTENTION : self::GROUP_POSITIVE,
            'value' => $value > 0 ? 'Есть изменения' : 'Не менялись',
        ];
    }

    /**
     * Статус организации
     *
     * @return bool Действует ли организация в настоящий момент
     */
    public function statusValue(): bool
    {
        return $this->card->isActive();
    }

    /**
     * Статус организации
     *
     * @param bool $value
     * @return array
     */
    public function status(bool $value): array
    {
        return [
            'group' => $value === true ? self::GROUP_POSITIVE : self::GROUP_NEGATIVE,
            'value' => $value === true ? 'Действует' : 'Не действует',
        ];
    }


    /**
     * Фондовооруженность
     *
     * @return float|null
     */
    public function capitallaborValue()
    {
        if (!$this->card->ЧислСотруд) {
            return null;
        }
        return round($this->fs->valueOf('balance->active->I. ВНЕОБОРОТНЫЕ АКТИВЫ->Основные средства',
                $this->card->employeeCountYear) / $this->card->ЧислСотруд, 2);
    }

    /**
     * Фондовооруженность
     *
     * @param float $value
     * @return array
     */
    public function capitallabor(float $value): array
    {
        return [
            'group' => $value > 1000 ? self::GROUP_POSITIVE : self::GROUP_ATTENTION,
            'value' => $this->fs::amountFormat($value),
        ];
    }

    /**
     * Зависимость от кредиторов
     *
     * @return float|null
     */
    public function lenderValue()
    {
        $fs = $this->fs;
        $line1700 = (int)$fs->valueOf(1700);
        if ($line1700 === 0) {
            return null;
        }
        return round(
            ((int)$fs->valueOf(1400) + (int)$fs->valueOf(1500) + (int)$fs->valueOf(1530) + (int)$fs->valueOf(1540)) / $line1700,
            2
        );
    }

    /**
     * Зависимость от кредиторов
     *
     * @param float $value
     * @return array
     */
    public function lender(float $value): array
    {
        return [
            'group' => $value >= 0.4 && $value <= 0.8 ? self::GROUP_POSITIVE : self::GROUP_ATTENTION,
            'value' => $value,
        ];
    }

    /**
     * Обеспеченность собственными средствами
     *
     * @return float|null
     */
    public function ownFoundsValue()
    {
        $fs = $this->fs;
        $line1200 = (int)$fs->valueOf(1200);
        if ($line1200 === 0) {
            return null;
        }
        return round(
            ((int)$fs->valueOf(1300) - (int)$fs->valueOf(1100)) / $line1200,
            2
        );
    }

    /**
     * Обеспеченность собственными средствами
     *
     * @param float $value
     * @return array
     */
    public function ownFounds(float $value): array
    {
        return [
            'group' => $value >= 0.1 ? self::GROUP_POSITIVE : self::GROUP_ATTENTION,
            'value' => ($value * 100) . '%',
        ];
    }

    /**
     * Коэффициент текущей ликвидности
     *
     * @return float|null
     */
    public function liquidityCurrentValue()
    {
        $fs = $this->fs;
        $bottom = (int)$fs->valueOf(1500) - (int)$fs->valueOf(1530) - (int)$fs->valueOf(1540);
        if ($bottom === 0) {
            return null;
        }
        return round(
            ((int)$fs->valueOf(1200) + (int)$fs->valueOf(1170)) / $bottom
            , 2
        );
    }

    /**
     * Коэффициент текущей ликвидности
     *
     * @param float $value
     * @return array
     */
    public function liquidityCurrent(float $value): array
    {
        return [
            'group' => $value >= 1.7 ? self::GROUP_POSITIVE : self::GROUP_ATTENTION,
            'value' => $value,
        ];
    }

    /**
     * Рентабельность по активам
     *
     * @return float|null
     */
    public function ROAValue()
    {
        $fs = $this->fs;
        $bottom = ((int)$fs->valueOf(1600) + (int)$fs->valueOf(1600, $fs->getYear() - 1)) / 2;
        if ($bottom === 0) {
            return null;
        }
        return round(
            (int)$fs->valueOf(2400) / $bottom
            , 2
        );
    }

    /**
     * Рентабельность по активам
     *
     * @param float $value
     * @return array
     */
    public function ROA(float $value): array
    {
        return [
            'group' => $value > 0 ? self::GROUP_POSITIVE : self::GROUP_ATTENTION,
            'value' => $value,
        ];
    }

    /**
     * Рентабельность по продажам
     *
     * @return float|null
     */
    public function ROSValue()
    {
        $fs = $this->fs;
        $line2110 = (int)$fs->valueOf(2110);
        if ($line2110 === 0) {
            return null;
        }
        return round(
            (int)$fs->valueOf(2200) / $line2110
            , 2
        );
    }

    /**
     * Рентабельность по продажам
     *
     * @param float $value
     * @return array
     */
    public function ROS(float $value): array
    {
        return [
            'group' => $value > 0 ? self::GROUP_POSITIVE : self::GROUP_ATTENTION,
            'value' => $value,
        ];
    }

    /**
     *
     *
     * @return float|null
     */
    public function financialAutonomyValue()
    {
        $fs = $this->fs;
        $line1700 = (int)$fs->valueOf(1700);
        if ($line1700 === 0) {
            return null;
        }
        return round((int)$fs->valueOf(1300) / $line1700, 2);
    }

    /**
     * Коэффициент финансовой автономии
     *
     * @param float $value
     * @return array
     */
    public function financialAutonomy(float $value): array
    {
        return [
            'group' => $value < 0.3 ? self::GROUP_NEGATIVE : ($value > 1 ? self::GROUP_POSITIVE : self::GROUP_ATTENTION),
            'value' => ($value * 100) . '%',
        ];
    }

    /**
     * Коэффициент финансовой устойчивости
     *
     * @return float|null
     */
    public function financialStabilityValue()
    {
        $fs = $this->fs;
        $line1700 = (int)$fs->valueOf(1700);
        if ($line1700 === 0) {
            return null;
        }
        return round(((int)$fs->valueOf(1300) + (int)$fs->valueOf(1400)) / $line1700, 2);
    }

    /**
     * Коэффициент финансовой устойчивости
     *
     * @param float $value
     * @return array|null
     */
    public function financialStability(float $value)
    {
        return [
            'group' => $value < 0.75 ? self::GROUP_ATTENTION : self::GROUP_POSITIVE,
            'value' => $value * 100,
        ];
    }

}