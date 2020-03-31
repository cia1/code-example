<?php

namespace common\components\zchb;

/**
 * Подробная информация по судебному разбирательству
 * Расширяет информацию, полученную классом CourtArbitration, помогает избежать дублирования информации в кеше.
 * К сожалению нет способа однозначно определить состояние и инстанцию судебного дела, поэтому они определяются коссвенно по ключевым словам. Это может работать плохо (
 *
 * @see CourtArbitration
 */
class CourtArbitrationCard extends CourtArbitration
{

    //Состояние судебного дела
    const STATUS_PROCESS = 1; //Рассматривается
    const STATUS_FINISH = 2; //Завершено
    const STATUS_APPEAL = 3; //Завершено, апелляция

    const INSTANCE_FIRST = 1; //Суд первой инстанции
    const INSTANCE_CASSATION = 2; //Суд кассационной инстанции
    const INSTANCE_APPEAL = 3; //Суд апелляционной инстанции
    const INSTANCE_SUPERVISORY = 4; //Суд надзорной инстанции

    protected $dataCard = []; //Хранит только дополнительную информацию

    /**
     * Название статуса
     *
     * @param int $status
     * @return string
     */
    public static function statusLabel(int $status): string
    {
        switch ($status) {
            default:
            case self::STATUS_PROCESS:
                return 'Рассматривается';
            case self::STATUS_FINISH:
                return 'Завершено';
            case self::STATUS_APPEAL:
                return 'Апелляция';
        }
    }

    /**
     * Название судебной инстанции
     *
     * @param int $instance
     * @return string
     */
    public static function instanceLabel(int $instance): string
    {
        switch ($instance) {
            default:
            case self::INSTANCE_FIRST:
                return 'Суд первой инстанции';
            case self::INSTANCE_CASSATION:
                return 'Суд кассационной инстанции';
            case self::INSTANCE_APPEAL:
                return 'Суд апелляционной инстанции';
            case self::INSTANCE_SUPERVISORY:
                return 'Суд надзорной инстанции';
        }
    }

    /**
     * Исключить из набора все дела, кроме дел, где контрагент выступает в указанной роли
     *
     * @param string $role
     * @see parent::ROLE_*
     */
    public function filterByRole(string $role)
    {
        foreach ($this->data as $id => $item) {
            if ($item['role'] !== $role) {
                unset($this->data[$id]);
            }
        }
    }

    /**
     * Исключить из набора все дела, кроме дел с указанным статусом
     *
     * @param int $status
     * @throws ZCHBAPIException
     * @see self::STATUS_*
     */
    public function filterByStatus(int $status)
    {
        foreach (array_keys($this->data) as $id) {
            $item = self::_merge($id);
            if ($item['status'] !== $status) {
                unset($this->data[$id]);
            }
        }
    }

    /**
     * Сортирует дела в указанном порядке
     *
     * @param string $order Имя метода, выполняющего сортировку
     */
    public function sort(string $order)
    {
        $order = 'sort' . ucfirst($order);
        if (method_exists($this, $order) === false) {
            return;
        }
        uasort($this->data, [$this, $order]);
    }

    protected static function sortAmount($a, $b)
    {
        if ($a['amount'] === $b['amount']) {
            return 0;
        }
        if ($a['amount'] < $b['amount']) {
            return 1;
        }
        return -1;
    }

    /**
     * Количество судебных дел
     *
     * @param int|null $status
     * @return int
     */
    public function count(int $status = null): int
    {
        if ($status === null) {
            return parent::count();
        }
        $cnt = 0;
        foreach ($this as $item) {
            if ($item['status'] === $status) {
                $cnt++;
            }
        }
        return $cnt;
    }

    /**
     * Объединяет данные базового класса и расширенного
     *
     * @return array
     * @throws ZCHBAPIException
     */
    public function current()
    {
        return $this->_merge($this->key());
    }

    /**
     * Содержит только данные, отсутствующие в CourtArbitration
     *
     * @param array $data
     * @return array
     */
    protected function filterCard(array $data): array
    {
        $instance = self::INSTANCE_FIRST;
        $status = self::STATUS_PROCESS;
        $data = self::filterByAlias([
            'type' => 'Тип',
            'category' => 'Категория',
            'chronology' => [
                'Хронология',
                function ($data) use (&$instance) {
                    $out = [];
                    foreach ($data as $date => $item) {
                        $doc = [];
                        $item['date'] = strtotime($date);
                        /** @noinspection PhpForeachNestedOuterKeyValueVariablesConflictInspection */
                        foreach ($item['docs'] as $date => $itemDoc) {
                            if (strpos($itemDoc, 'суд апелляционной инстанции') !== false) {
                                $instance = self::INSTANCE_APPEAL;
                            }
                            $doc[] = [
                                'date' => strtotime($date),
                                'name' => $itemDoc,
                            ];
                        }
                        usort($doc, function ($a, $b) {
                            if ($a['date'] === $b['date']) {
                                return 0;
                            }
                            if ($a['date'] > $b['date']) {
                                return 1;
                            }
                            return -1;
                        });
                        $item['docs'] = $doc;
                        $out[] = $item;
                    }
                    usort($out, function ($a, $b) {
                        if ($a['date'] === $b['date']) {
                            return 0;
                        }
                        if ($a['date'] > $b['date']) {
                            return 1;
                        }
                        return -1;
                    });
                    return $out;
                },
            ],
        ], $data);
        $name = $data['chronology'][count($data['chronology']) - 1]['docs'];
        $name = $name[count($name) - 1]['name'];
        if (
            mb_substr($name, 0, 9, 'UTF-8') === 'Решение (' ||
            strpos($name, 'Возвратить заявление ') !== false ||
            strpos($name, 'Прекратить производство по делу') !== false ||
            strpos($name, 'Удовлетворить ходатайство') !== false ||
            strpos($name, 'Удовлетворить иск ') !== false ||
            strpos($name, 'Резолютивная часть решения суда') !== false ||
            strpos($name, 'Решения и постановления') !== false
        ) {
            $status = self::STATUS_FINISH;
        }
        $data['status'] = $status;
        $data['instance'] = $instance;
        return $data;
    }

    /**
     * @param       $id
     * @return array|null
     * @throws ZCHBAPIException
     */
    protected function requestCard(string $id)
    {
        if (array_key_exists($id, $this->dataCard) === true) {
            return $this->dataCard[$id];
        }
        $this->dataCard[$id] = $this->helper->requestOne('court-arbitration-card', ['id' => $id], function ($data) {
            if ($data === null) {
                return [];
            }
            return $this->filterCard($data);
        });
        return $this->dataCard[$id];
    }

    /**
     * @param string $id
     * @return array
     * @throws ZCHBAPIException
     */
    private function _merge(string $id): array
    {
        $item = $this->data[$id];
        $card = $this->requestCard($id);
        if ($card === null) {
            $card = [
                'type' => null,
                'status' => null
            ];
        }
        return array_merge($item, $card);
    }

}