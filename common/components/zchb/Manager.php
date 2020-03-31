<?php

namespace common\components\zchb;

use yii\web\NotFoundHttpException;

/**
 * Менеджер доступа к данным, полученным через API ЗАЧЕСТНЫЙБИЗНЕС
 * Методы *Instance возвращают объект или NULL, если данных по указанному запросу нет.
 * Методы *New возвращают объект даже в том случае, если данных нет.
 */
class Manager
{
    public $helper;

    /**
     * @param ZCHBHelper $helper
     */
    public function __construct(ZCHBHelper $helper)
    {
        $this->helper = $helper;
    }

    /**
     * "Карточка контрагента" (основная информация)
     * @param string $id ОГРН/ИНН/ОГРНИП/ИННФЛ
     * @return Card
     * @throws NotFoundHttpException
     * @throws ZCHBAPIException
     */
    public function cardInstance(string $id)
    {
        if ($id) {
            /** @var Card $card */
            $card = Card::instance($id, $this->helper);
        } else {
            $card = null;
        }
        if ($card === null) {
            throw new NotFoundHttpException('К сожалению по данному контрагенту информации нет.');
        }
        return $card;
    }

    /**
     * Судебные дела контрагента
     * @param string $id ОГРН/ИНН/ОГРНИП/ИННФЛ
     * @return CourtArbitration|null
     * @throws ZCHBAPIException
     */
    public function courtArbitrationInstance(string $id)
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return CourtArbitration::instance($id);
    }

    /**
     * Судебные дела контрагента
     * @param string $id ОГРН/ИНН
     * @return CourtArbitration
     * @throws ZCHBAPIException
     */
    public function courtArbitrationNew(string $id)
    {
        return new CourtArbitration($id, $this->helper);
    }

    /**
     * Лента изменений
     * @param string $ogrn ОГРН/ОГРНИП
     * @return Diff
     * @throws ZCHBAPIException
     */
    public function diffNew(string $ogrn)
    {
        return new Diff($ogrn, $this->helper);
    }

    /**
     * Контакты
     * @param string $id ОГРН/ИНН/ОГРНИП/ИННФЛ
     * @return Contact
     * @throws ZCHBAPIException
     */
    public function contactNew(string $id)
    {
        return new Contact($id, $this->helper);
    }

    /**
     * Финансовая отчётность
     * @param string $id ОГРН/ИНН/ОГРНИП/ИННФЛ
     * @return FinancialStatement|null
     * @throws ZCHBAPIException
     */
    public function financialStatementInstance(string $id)
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return FinancialStatement::instance($id, $this->helper);
    }

    /**
     * Финансовая отчётность
     * @param string $id ОГРН/ИНН/ОГРНИП/ИННФЛ
     * @return FinancialStatement
     * @throws ZCHBAPIException
     */
    public function financialStatementNew(string $id)
    {
        return new FinancialStatement($id, $this->helper);
    }

    /**
     * Карточка физического лица
     * @param string $inn ИННФЛ
     * @return FlCard|null
     * @throws ZCHBAPIException
     */
    public function flCardInstance(string $inn)
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return FlCard::instance($inn, $this->helper);
    }

    /**
     * Связи
     * @return ConnectHelper
     * @throws ZCHBAPIException
     */
    public function connectHelperNew()
    {
        return new ConnectHelper($this->helper);
    }

    /**
     * Карточки судебных дел (содержат расширенную информацию)
     * @param string $ogrn ОГРН
     * @param string $inn  ИНН
     * @return CourtArbitrationCard
     * @throws ZCHBAPIException
     */
    public function courtArbitrationCardNew(string $ogrn, string $inn)
    {
        $arbitrationCard = new CourtArbitrationCard(null, $this->helper);
        $arbitrationCard->findByID($ogrn, $inn);
        return $arbitrationCard;
    }

}