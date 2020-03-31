<?php

namespace common\components\zchb;

/**
 * Карточка физического лица.
 * По сути "обёртка" для данных, возвращаемых API, предоставляющая более дружественный интерфейс.
 *
 * @see https://zachestnyibiznesapi.ru/#api-fl-card
 * @property string  $fio      ФИО
 * @property int     $inn      ИНН
 * @property string  $region   Регион ведения бизнеса
 * @property array[] $director Организации, в которых физ.лицо является руководителем
 * @property array[] $founder  Организации, в которых физ.лицо является учредителем
 * @property array[] $ie       ИП физического лица
 */
class FlCard extends ZCHBContainer
{

    /**
     * Плоский список (только название) организаций, в которых физическое лицо является учредителем
     *
     * @return array
     */
    public function founderList(): array
    {
        $founder = [];
        foreach ($this->founder as $item) {
            if ($item['Активность'] === 'Действующее') {
                $founder[] = $item['НаимЮЛСокр'];
            }
        }
        return $founder;
    }

    protected static function method(): string
    {
        return 'fl-card';
    }

    protected function filter(array $data): array
    {
        return self::filterByAlias([
            'fio' => 'ФИО',
            'inn' => 'ИННФЛ',
            'region' => 'РегионВедБизнеса',
            'director' => [
                'Руководитель',
                [
                    'НаимЮЛСокр',
                    'Активность',
                    'ОГРН',
                    'ИНН',
                    'Адрес',
                    'registrationDate' => function ($data) {
                        return strtotime($data['ДатаРег'] ?? null);
                    },
                ],
            ],
            'founder' => [
                'Учредитель',
                ['НаимЮЛСокр', 'Активность', 'ОГРН', 'ИНН', 'Адрес'],
            ],
            'ie' => ['ИП', ['ФИО', 'Активность']],
        ], $data);
    }

}
