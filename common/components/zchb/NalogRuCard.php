<?php


namespace common\components\zchb;

use common\models\dossier\NalogRuCard as NalogRuCardCommon;
use common\models\dossier\NalogRuRelation;
use yii\base\InvalidConfigException;
use yii\db\ActiveQuery;

/**
 * @property NalogRuTask $taskAddress
 */
class NalogRuCard extends NalogRuCardCommon
{

    public static function instanceByINN(int $inn): self
    {
        $card = new static();
        $card->inn = $inn;
        $card->status_address = self::STATUS_WITHOUT;
        return $card;
    }

    /**
     * Загружает данные из API и сохраняет в базе данных
     * @param bool $createTaskAddress Также запустить задачу сбора связей по адресам
     * @return bool TRUE, если данные были успешно загружены и сохранены
     * @throws InvalidConfigException
     */
    public function loadFromAPI(bool $createTaskAddress = false): bool
    {
        try {
            $nalog = new NalogRuHelper();
            $card = $nalog->card($this->inn);
        } catch (ZCHBAPIException $e) {
            $this->addError('inn', $e->getMessage());
            return false;
        }
        $this->date = time();
        if ($card === null) {
            $this->setNotExists();
            NalogRuRelation::deleteAll(['inn' => $this->inn]);
            $this->save();
            return false;
        }
        $this->not_exists = false;

        $massAddress = $card['masaddress'] ?? [];
        $card = $card['vyp'];
        if (isset($card['usn']) === true && $card['usn'] == 1) {
            $taxationForm = self::TAXATION_USN;
        } elseif (isset($card['eshn']) === true && $card['eshn'] == 1) {
            $taxationForm = self::TAXATION_ESHN;
        } elseif (isset($card['envd']) === true && $card['envd'] == 1) {
            $taxationForm = self::TAXATION_ENVD;
        } else {
            $taxationForm = self::TAXATION_OSN;
        }
        $this->load([
            'title' => $card['НаимЮЛПолн'] ?? null,
            'inn_date' => $card['ДатаПостУч'] ?? null,
            'ogrn' => $card['ОГРН'] ?? null,
            'ogrn_date' => $card['ДатаОГРН'] ?? null,
            'creation_method' => $card['НаимСпОбрЮЛ'] ?? null,
            'nalog_department_name' => $card['НаимНО'] ?? null,
            'nalog_department_local' => $card['НаимРО'] ?? null,
            'kpp' => $card['КПП'] ?? null,
            'okved' => $card['КодОКВЭД'] ?? null,
            'capital' => (int)($card['СумКап'] ?? null),
            'okopf' => $card['okopf12'] ?? null,
            'foreign' => (bool)($card['foreignorg'] ?? false),
            'taxation_form' => $taxationForm,
            'liquidated' => $card['liquidated'] ?? false,
            'rsmp_category' => $card['rsmpcategory'] == 0 ? null : $card['rsmpcategory'],
            'rsmp_date' => $card['rsmpdate'] ?? null,
            'postal_code' => $card['Индекс'] ?? null,
            'region_type' => isset($card['ТипРегион']) === true ? mb_strtolower($card['ТипРегион']) : null,
            'region_name' => isset($card['НаимРегион']) === true ? self::_ucFirst($card['НаимРегион']) : null,
            'city_type' => isset($card['ТипГород']) === true ? mb_strtolower($card['ТипГород']) : null,
            'city_name' => isset($card['НаимГород']) === true ? self::_ucFirst($card['НаимГород']) : null,
            'street_type' => isset($card['ТипУлица']) === true ? mb_strtolower($card['ТипУлица']) : null,
            'street_name' => isset($card['НаимУлица']) === true ? self::_ucFirst($card['НаимУлица']) : null,
            'building' => $card['Дом'] ?? null,
            'block' => $card['Корпус'] ?? null,
            'incorrect_info_comment' => $card['СвНедАдресЮЛ'][0]['ТекстНедАдресЮЛ'] ?? null
        ], '');
        unset($card);
        if (count($massAddress) === 0) {
            $this->status_address = self::STATUS_SUCCESS;
        } elseif ($createTaskAddress === false) {
            $this->status_address = self::STATUS_WITHOUT;
        } else {
            $this->status_address = self::STATUS_PROCESS;
        }
        if ($this->save() === false) {
            return false;
        }
        //Поставить задачу загрузки данных по массовым адресам
        if ($createTaskAddress === true) {
            $task = $this->taskAddress;
            if ($task === null) {
                $task = new NalogRuTask();
                $task->inn = $this->inn;
                $task->type = self::TYPE_ADDRESS;
                $this->populateRelation('taskAddress', $task);
            }
            $task->relations = $massAddress;
            $result = $task->save();
            $task->relations = $massAddress;
            return $result;

        }
        return true;
    }

    public function getTaskAddress(): ActiveQuery
    {
        return $this->hasOne(NalogRuTask::class, ['inn' => 'inn'])->andWhere(['type' => self::TYPE_ADDRESS]);
    }

    private static function _ucFirst(string $value): string
    {
        $value = mb_strtolower($value, 'UTF-8');
        return mb_strtoupper(mb_substr($value, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($value, 1);
    }

}