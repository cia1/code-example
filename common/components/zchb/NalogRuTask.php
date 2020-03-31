<?php


namespace common\components\zchb;

use common\models\dossier\NalogRuTask as NalogRuTaskCommon;
use Throwable;
use yii\db\StaleObjectException;

/**
 * Нужно периодически вызывать self::process() до тех пор, пока не будут загружены все связанные данные.
 */
class NalogRuTask extends NalogRuTaskCommon
{

    /**
     * Загружает одну карточку из списка
     * @throws Throwable
     * @throws StaleObjectException
     */
    public function process()
    {
        $relation = $this->relations;
        while ($item = array_shift($relation)) {
            $card = NalogRuCard::find()->select(['inn', 'date'])->where(['inn' => $item['massinn']])->one();
            if ($card !== null) { //карточка уже суещствует
                /** @var NalogRuCard $card */
                if ($card->isActual() === true) {
                    continue;
                } else {
                    $card->delete();
                }
            }
            $card = NalogRuCard::instanceByINN($item['massinn']);
            if ($card->loadFromAPI(false) === true) {
                $this->card->addRelation(NalogRuCard::TYPE_ADDRESS, $card->inn);
            }
            if (count($relation) > 0) {
                $this->relations = $relation;
                $this->save();
                return;
            }
        }
        $this->card->saveStatusAddress(NalogRuCard::STATUS_SUCCESS);
        $this->delete(); //задача выполнена и больше не нужна
    }

}