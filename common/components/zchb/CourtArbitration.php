<?php

namespace common\components\zchb;

/**
 * Список судебных дел компании
 *
 * @see https://zachestnyibiznesapi.ru/#api-courtlist
 */
class CourtArbitration extends ZCHBIteratorContainer
{

    const ROLE_APPLICANT = 'applicant'; //Истец
    const ROLE_DEFENDANT = 'defendant'; //Ответчик
    const ROLE_THIRD = 'third'; //Третья сторона
    const ROLE_OTHER = 'other'; //Иной участник

    public $ogrn;
    public $inn;

    public static function implodeItem(array $item, bool $requisite = false, string $separator = '; '): string
    {
        $out = '';
        foreach ($item as $subItem) {
            if ($out !== '') {
                $out .= $separator;
            }
            $out .= $subItem['Наименование'] ?? '(нет)';
            if ($requisite === true) {
                $s = '';
                if (isset($subItem['ИНН']) === true) {
                    $s .= 'ИНН ' . $subItem['ИНН'];
                }
                if (isset($subItem['ОГРН']) === true) {
                    if ($s) {
                        $s .= ', ';
                    }
                    $s .= 'ОГРН ' . $subItem['ОГРН'];
                }
                if ($s) {
                    $out .= ' (' . $s . ')';
                }
            }
        }
        return $out;
    }

    public function findByID(string $ogrn, string $inn = null): bool
    {
        $this->ogrn = $ogrn;
        $this->inn = $inn;
        return parent::findByID($ogrn);
    }

    /**
     * Количество судебных дел в роли исца
     *
     * @return int
     */
    public function countApplicant(): int
    {
        return $this->_count(self::ROLE_APPLICANT);
    }

    /**
     * Количество судебных дел в роли ответчика
     *
     * @return int
     */
    public function countDefendant(): int
    {
        return $this->_count(self::ROLE_DEFENDANT);
    }

    /**
     * Количество судебных дел в роли третьей стороны
     *
     * @return int
     */
    public function countThirdParty(): int
    {
        return $this->_count(self::ROLE_THIRD);
    }

    /**
     * Количество судебных дел в других ролях
     *
     * @return int
     */
    public function countOtherParty(): int
    {
        return $this->_count(self::ROLE_OTHER);
    }

    public function roleTitle(string $role): string
    {
        switch ($role) {
            case self::ROLE_APPLICANT:
                return 'истец';
            case self::ROLE_DEFENDANT:
                return 'ответчик';
            case self::ROLE_THIRD:
                return 'третья сторона';
            case self::ROLE_OTHER:
            default:
                return 'иное';
        }
    }

    protected static function method(): string
    {
        return 'court-arbitration';
    }

    protected function filter(array $data): array
    {
        $data = $data['точно'];
        if (isset($data[0]) === true && $data[0] === 'Данных нет') {
            return [];
        }
        $data = $data['дела'];
        return parent::filter($data);
    }

    protected function filterItem(array $data)
    {
        $data = self::filterByAlias([
            self::ROLE_APPLICANT => 'Истец',
            self::ROLE_DEFENDANT => 'Ответчик',
            self::ROLE_THIRD => 'Третье лицо',
            self::ROLE_OTHER => 'Иной участник',
            'number' => 'НомерДела',
            'amount' => 'СуммаИска',
            'dateStart' => 'СтартДата',
        ], $data);
        $role = self::ROLE_OTHER;
        foreach ($data[self::ROLE_APPLICANT] as $item) {
            if ($this->ogrn == $item['ОГРН'] || $this->inn == $item['ИНН']) {
                $role = self::ROLE_APPLICANT;
                break;
            }
        }
        if ($role === self::ROLE_OTHER) {
            foreach ($data[self::ROLE_DEFENDANT] as $item) {
                if ($this->ogrn == $item['ОГРН'] || $this->inn == $item['ИНН']) {
                    $role = self::ROLE_DEFENDANT;
                    break;
                }
            }
        }
        if ($role === self::ROLE_OTHER) {
            foreach ($data[self::ROLE_THIRD] ?? [] as $item) {
                if ($this->ogrn == $item['ОГРН'] || $this->inn == $item['ИНН']) {
                    $role = self::ROLE_THIRD;
                    break;
                }
            }
        }
        $data['role'] = $role;
        return $data;
    }

    private function _count(string $attribute): int
    {
        $cnt = 0;
        $inn = $this->inn ?? -1;
        foreach ($this->data as $item) {
            if ($this->ogrn == ($item[$attribute]['ОГРН'] ?? null) || $inn == ($item[$attribute]['ИНН'] ?? null)) {
                $cnt++;
            }
        }
        return $cnt;
    }

}