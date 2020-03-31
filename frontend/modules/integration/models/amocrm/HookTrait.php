<?php

namespace frontend\modules\integration\models\amocrm;

use common\models\amocrm\User;
use Throwable;
use yii\db\StaleObjectException;

trait HookTrait
{

    public function setCompanyId(int $userId)
    {
        /** @var User $user */
        $user = User::find()->where(['user_id' => $userId])->one();
        $this->company_id = $user->company_id;
    }

    public function hookAdd(int $userId, array $data): bool
    {
        $this->isNewRecord = !(static::find()->where(['id' => $data['id']])->exists());
        if ($this->isNewRecord === false) {
            return $this->hookUpdate($userId, $data);
        }
        $this->loadAmoData($userId, $data);

        return $this->save();
    }

    public function hookUpdate(int $userId, array $data): bool
    {
        $this->isNewRecord = !(static::find()->where(['id' => $data['id']])->exists());
        if ($this->isNewRecord === true) {
            return $this->hookAdd($userId, $data);
        }
        $this->loadAmoData($userId, $data);
        $this->setOldAttributes(['id' => $this->id]);
        return $this->save();
    }

    /**
     * Обработчик веб-хука удаления сущности
     *
     * @param int   $userId Идентификатор пользователя AmoCRM
     * @param array $data
     * @throws Throwable
     * @throws StaleObjectException
     */
    public function hookDelete(/** @noinspection PhpUnusedParameterInspection */int $userId, array $data)
    {
        static::findOne($data['id'])->delete();
    }

    /**
     * Обработчик веб-хука добавления примечания к какой-либо сущности
     *
     * @param int   $userId Идентификатор пользователя AmoCRM
     * @param array $data
     * @return bool
     */
    public function hookNote(int $userId, array $data): bool
    {
        $data = $data['note'];
        //В note_type могут приходить недокументированные числа
        if (in_array($data['note_type'], Note::TYPE) === false) {
            return false;
        }
        $data['note_type'] = (int)$data['note_type'];
        $note = new Note();
        $note->load($data, '');
        $note->id = $data['id'];
        $note->setCompanyId($userId);
        $note->isNewRecord = !($note::find()->where(['id' => $note->id])->exists());
        return $note->save();
    }


    protected function loadAmoData(int $userId, $data)
    {
        $this->load($data, '');
        $this->id = $data['id'];
        $this->setCompanyId($userId);
    }
}