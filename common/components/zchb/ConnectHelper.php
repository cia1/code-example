<?php

namespace common\components\zchb;

class ConnectHelper
{
    /** @var bool Данные по массовым адресам загружены */
    public $nalogRuAddressReady = false;

    private $_helper;

    /**
     * @param ZCHBHelper|null $helper
     * @throws ZCHBAPIException
     */
    public function __construct(ZCHBHelper $helper = null)
    {
        if ($helper === null) {
            $helper = ZCHBHelper::instance();
        }
        $this->_helper = $helper;
    }

    /**
     * @param Card     $card
     * @param int|null $flINN
     * @return array:
     *  bool        $byAddress  Связь по адресу
     *  bool        $byDirector Связь по руководителю
     *  bool        $byFounder  Связь по учредителю
     *  string      $fio        Ф.И.О. лица, по которому обнаружена связь
     *  string|null $post       Должность лица в организации
     *  int         $flINN      ИНН лица, по которому обранужена связь
     *
     * @throws ZCHBAPIException
     */
    public function get(Card $card, int $flINN = null): array
    {
        if ($card->isIndividual === true) {
            $directors = $founders = [['inn' => $card->ИНН]];
        } else {
            $directors = $card->directors;
            $founders = $card->founders;
        }
        $out = [];
        foreach ($directors as $fl) {
            if ($flINN !== null && $flINN !== $fl['inn']) {
                continue;
            }
            /** @noinspection NullPointerExceptionInspection */
            $fl = FlCard::instance($fl['inn'], $this->_helper);
            if ($fl === null) {
                continue;
            }
            /** @var FlCard $fl */
            foreach ($fl->director as $item) {
                if ($item['ОГРН'] == $card->ОГРН || $item['ИНН'] == $card->ИНН) {
                    continue;
                }
                $item['byAddress'] = false;
                $item['byDirector'] = true;
                $item['byFounder'] = false;
                $item['fio'] = $fl->fio;
                $item['flINN'] = $fl->inn;
                $this->_appendFromCard($item);
                $out[$item['ОГРН'] ?? $item['ИНН']] = $item;
            }
        }
        foreach ($founders as $fl) {
            /** @noinspection NullPointerExceptionInspection */
            $fl = FlCard::instance($fl['inn'], $this->_helper);
            if ($fl === null) {
                continue;
            }
            /** @var FlCard $fl */
            foreach ($fl->founder as $item) {
                $id = $item['ОГРН'] ?? $item['ИНН'] ?? null;
                if ($id === null) {
                    continue;
                }
                if ($id == $card->ОГРН) {
                    continue;
                }
                if (isset($out[$id]) === false) {
                    $item['fio'] = $fl->fio;
                    $item['flINN'] = $fl->inn;
                    $item['byAddress'] = false;
                    $item['byDirector'] = false;
                    $this->_appendFromCard($item);
                    $out[$id] = $item;
                }
                $out[$id]['byFounder'] = true;
            }
        }
        $nalogCard = NalogRuCard::findByINN($card->ИНН);
        if ($nalogCard === null || $nalogCard->status_address !== NalogRuCard::STATUS_SUCCESS) {
            return $out;
        }
        $this->nalogRuAddressReady = true;
        foreach ($nalogCard->relatedAddress as $nalogItem) {
            $id = $nalogItem->ogrn ?? $nalogItem->inn;
            if (isset($out[$id]) === true) {
                $out[$id]['byAddress'] = true;
            } else {
                $out[$id] = [
                    'byAddress' => true,
                    'byDirector' => false,
                    'byFounder' => false,
                    'НаимЮЛСокр' => $nalogItem->title,
                    'ОГРН' => $nalogItem->ogrn,
                    'ИНН' => $nalogItem->inn,
                    'Адрес' => $nalogItem->address,
                    'registrationDate' => $nalogItem->inn_date,
                    'fio' => null,
                    'flINN' => null,
                    'post' => null
                ];
            }
        }
        return $out;
    }

    /**
     * @param array $data
     * @throws ZCHBAPIException
     */
    private function _appendFromCard(array &$data)
    {
        $data['post'] = null;
        if (isset($data['ОГРН']) === false) {
            return;
        }
        $card = Card::instance($data['ОГРН'], $this->_helper);
        if ($card === null) {
            return;
        }
        /** @var Card $card */
        foreach ($card->directors as $d) {
            if (isset($d['inn'], $d['post']) && $data['flINN'] == $d['inn']) {
                $data['post'] = $d['post'];
                break;
            }
        }
    }

}