<?php

namespace frontend\modules\integration\models\bitrix24;

use frontend\modules\integration\helpers\Bitrix24Helper;

/**
 * Выполняет обработку веб-хуков Битрикс24
 * В веб-хуках от сервера Битрикс24 "приходит" только ID сущности, поэтому всю необходимую информацию приходится "дёргать" через REST API.
 */
trait HookTrait
{

    /** @var Bitrix24Helper */
    protected $helper;

    /**
     * Загружает из базы данных экземплаяр класса или создаёт сущность, загружая данные с сервера Битрикс24
     *
     * @param int            $companyId Идентификатор компании КУБ
     * @param int            $id        Идентификатор сущности
     * @param Bitrix24Helper $helper
     * @return static
     */
    public static function findOrCreate(int $companyId, int $id, Bitrix24Helper $helper)
    {

        $model = static::findOne(['company_id' => $companyId, 'id' => $id]);
        if ($model === null) {
            $model = new static();
            $model->hookCreate($companyId, $id, $helper);
        }
        return $model;
    }

    /**
     * Модель должна загрузить данные из REST API и вернуть массив с данными
     * В простейшем случае это return $this->helper->rest('<url>')
     *
     * @param int $id
     * @return mixed
     */
    abstract function loadFromRest(int $id);

    public function setHelper(Bitrix24Helper $helper)
    {
        $this->helper = $helper;
    }

    /**
     * Обработчик веб-хука создания записи
     *
     * @param int            $companyId Идентификатор компании КУБ
     * @param int            $id        Идентификатор сущности в Битрикс24
     * @param Bitrix24Helper $helper
     */
    public function hookCreate(int $companyId, int $id, Bitrix24Helper $helper)
    {
        $this->helper = $helper;
        $this->company_id = $companyId;
        $data = $this->loadFromRest($id);
        if (is_array($data) === false) {
            return;
        }
        $this->id = $id;
        $this->isNewRecord = !static::find()->where(['company_id' => $companyId, 'id' => $id])->exists();
        $this->load($this->prepareRestData(array_change_key_case($data)), '');
        $this->save();
    }

    /**
     * Обработчик веб-хука изменения записи
     * Т.к. на соответствующей сущности может ещё не существовать, вызывает hookCreate для создания/обновления
     *
     * @param int            $companyId Идентификатор компании КУБ
     * @param int            $id
     * @param Bitrix24Helper $helper
     */
    public function hookUpdate(int $companyId, int $id, Bitrix24Helper $helper)
    {
        $this->hookCreate($companyId, $id, $helper);
    }

    /**
     * Обаботчик веб-хука удаления записи
     *
     * @param int            $companyId Идентификатор компании КУБ
     * @param int            $id
     * @param Bitrix24Helper $helper
     */
    public function hookDelete(int $companyId, int $id, /** @noinspection PhpUnusedParameterInspection */ Bitrix24Helper $helper)
    {
        static::deleteAll(['company_id' => $companyId, 'id' => $id]);
    }

    /**
     * Обработка данных, полученных от сервера Битрикс24 перед её загрузкой в модель.
     * Перегрузите этот метод, если требуется выполнить особые проверки или преобразования.
     *
     * @param array $data Данные, полученные от сервера Битрикс24
     * @return array Обработанные данные, пригодные для загруки в модель (self)
     */
    public function prepareRestData(array $data)
    {
        return $data;
    }

}