<?php

namespace frontend\modules\integration\models;

use common\models\employee\Employee;
use Exception;
use frontend\modules\integration\helpers\AMOcrmHelper;
use yii\base\Model;
use yii\helpers\Url;

/**
 * Форма настроек интеграции AMOcrm
 *
 */
class AmocrmSettingsForm extends Model
{

    /** @var string Логин */
    public $login;
    /** @var int Ключ доступа к API */
    public $apiKey;
    /** @var string Имя хоста */
    public $host;

    /** @inheritDoc */
    public function rules(): array
    {
        return [
            [['login', 'apiKey', 'host'], 'required'],
            ['login', 'email'],
            ['apiKey', 'string', 'min' => 32, 'max' => 40],
            [
                'host',
                'filter',
                'filter' => function (string $value) {
                    if (strtolower(substr($value, -10)) === '.amocrm.ru') {
                        $value = mb_substr($value, 0, -10);
                    }
                    return $value;
                },
            ],
            ['host', 'match', 'pattern' => '/^[A-Za-z0-9_\.-]+$/', 'message' => 'Поле "Ваш домен" может содержать только символы a-z.'],
        ];
    }

    /** @inheritDoc */
    public function attributeLabels()
    {
        return [
            'login' => 'Логин',
            'apiKey' => 'Ключ доступа (ваш API ключ)',
            'host' => 'Ваш домен AMOcrm (XXX.amocrm.ru)',
        ];
    }

    /**
     * Выполняет валидацию и сохраняет конфигурацию в базе данных
     *
     * @param Employee $user
     * @return bool
     */
    public function storeToUser(Employee $user)
    {
        if ($this->validate() !== true) {
            return false;
        }
        if ($this->_testConnection() === false) {
            return false;
        }
        return $user->saveIntegration(Employee::INTEGRATION_AMOCRM, $this->toArray());
    }

    private function _testConnection()
    {
        try {
            $amo = new AMOcrmHelper($this->login, $this->apiKey, $this->host);
            if ($amo->setHook(Url::home(true) . 'integration/amocrm/hook', AMOcrmHelper::SUPPORTED_EVENTS) === false) {
                $this->addError('apiKey', 'Не получилось добавить веб-хук. Проверьте права доступа');
            }
            return true;
        } catch (Exception $e) {
            $this->addError('login', $e->getMessage());
            return false;
        }

    }

}
