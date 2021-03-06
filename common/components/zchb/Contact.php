<?php

namespace common\components\zchb;

/**
 * Контактная информация
 *
 * @see https://zachestnyibiznesapi.ru/#api-contacts
 * @property string|null ПочтаРосРаб Электронная почта в реестре РосРаб
 * @property string|null ПочтРОССТАТ Электронная почта в реестре РОССТАТ
 * @property string|null ПочтПерсРКН Электронная почта в реестре персональных данных РосКомНадзора
 * @property string|null ПочтЗАКУПКИ Электронная почта в реестре ГосЗакупки
 * @property string|null ТелРосРаб   Телефонный номер в реестре РосТруд
 * @property string|null ТелРОССТАТ  Телефонный номер в реестре РОССТАТ
 * @property string|null ТелПерсРКН  Телефонный номер в реестре персональных данных РосКомНадзора
 * @property string|null ТелЗАКУПКИ  Телефонный номер в реестре РосТруд
 * @property string|null НомТелФНС   Телефоный номер по данным ФНС
 */
class Contact extends ZCHBContainer
{

    protected static function method(): string
    {
        return 'contacts';
    }

    protected function filter(array $data): array
    {
        return self::filterByAlias([
            'ПочтаРосРаб',
            'ПочтРОССТАТ',
            'ПочтПерсРКН',
            'ПочтЗАКУПКИ',
            'ТелРосРаб',
            'ТелРОССТАТ',
            'ТелПерсРКН',
            'ТелЗАКУПКИ',
            'НомТелФНС',
        ], $data);
    }

    protected static function attributeLabels(): array
    {
        return [
            'ПочтаРосРаб' => 'Электронная почта в реестре РосРаб',
            'ПочтРОССТАТ' => 'Электронная почта в реестре РОССТАТ',
            'ПочтПерсРКН' => 'Электронная почта в реестре персональных данных РосКомНадзора',
            'ПочтЗАКУПКИ' => 'Электронная почта в реестре ГосЗакупки',
            'ТелРосРаб' => 'Телефонный номер в реестре РосТруд',
            'ТелРОССТАТ' => 'Телефонный номер в реестре РОССТАТ',
            'ТелПерсРКН' => 'Телефонный номер в реестре персональных данных РосКомНадзора',
            'ТелЗАКУПКИ' => 'Телефонный номер в реестре РосТруд',
            'Тел' => 'Телефонный номер в реестре РОССТАТ',
            'НомТелФНС' => 'Телефоный номер по данным ФНС',
        ];
    }

}