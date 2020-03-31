<?php

namespace frontend\modules\integration\models;

use common\models\employee\Employee;
use Exception;
use frontend\modules\integration\helpers\ImapHelper;
use kdn\yii2\validators\DomainValidator;
use yii\base\Model;

/**
 * Форма настроек интеграции электронной почты
 *
 * @property-write string $email Устанавливает параметры серверов на основе адреса электронной почты
 */
class EmailSettingsForm extends Model
{

    //Конфигурация "по умолчанию" с индексацией по доменам почтовых серверов
    const DEFAULT_SERVER_CONFIGURATION = [
        'yandex.ru' => [
            'imapHost' => 'imap.yandex.ru',
            'imapEncryption' => ImapHelper::ENCRYPTION_SSLTLS,
            'imapPort' => 993,
            'smtpHost' => 'smtp.yandex.ru',
            'smtpEncryption' => ImapHelper::ENCRYPTION_SSLTLS,
            'smtpPort' => 465,
        ],
        'mail.ru' => [
            'imapHost' => 'imap.mail.ru',
            'imapEncryption' => ImapHelper::ENCRYPTION_SSLTLS,
            'imapPort' => 993,
            'smtpHost' => 'smtp.mail.ru',
            'smtpEncryption' => ImapHelper::ENCRYPTION_SSLTLS,
            'smtpPort' => 465,
        ],
        'gmail.com' => [
            'imapHost' => 'imap.gmail.com',
            'imapEncryption' => ImapHelper::ENCRYPTION_SSLTLS,
            'imapPort' => 993,
            'smtpHost' => 'smtp.gmail.com',
            'smtpEncryption' => ImapHelper::ENCRYPTION_SSLTLS,
            'smtpPort' => 465,
        ],
    ];

    /**
     * Возвращает массив доступных способов шифрования для использования в выпадающем HTML-списке
     *
     * @return string[]
     */
    public static function SSLList()
    {
        return [
            ImapHelper::ENCRYPTION_NO => 'нет',
            ImapHelper::ENCRYPTION_SSLTLS => 'SSL/TLS',
        ];
    }

    /** @var string Сервер входящей почты */
    public $imapHost;
    /** @var int Порт входящей почты */
    public $imapPort;
    /** @var string Использовать ли защищённое соединение */
    public $imapEncryption;
    /** @var string E-mail (он же логин на сервере входящей почты) */
    public $imapEmail;
    /** @var string Пароль на сервере входящей почты */
    public $imapPassword;
    /** @var string Сервер исходящей почты */
    public $smtpHost;
    /** @var int Номер сервера исходящей почты */
    public $smtpPort;
    /** @var string Использовать ли защищённое соединение */
    public $smtpEncryption;
    /** @var string E-mail (он же логин на сервере исходящей почты) */
    public $smtpEmail;
    /** @var string Пароль на сервере исходящей почты */
    public $smtpPassword;
    /** @var int TIMESTAMP-дата подключения интеграции */
    public $date;

    /** @inheritDoc */
    public function rules(): array
    {
        return [
            [['imapEncryption', 'smtpEncryption'], 'default', 'value' => ImapHelper::ENCRYPTION_SSLTLS],
            ['imapPort', 'default', 'value' => 993],
            ['smtpPort', 'default', 'value' => 465],
            [['imapEmail', 'smtpEmail', 'imapHost', 'imapPort', 'imapEncryption', 'imapPassword', 'smtpHost', 'smtpPort', 'smtpEncryption', 'smtpPassword'], 'required'],
            [['imapHost', 'smtpHost'], DomainValidator::class],
            [['imapPort', 'smtpPort'], 'integer', 'min' => 1, 'max' => 65535],
            [['imapEncryption', 'smtpEncryption'], 'string'],
            [['imapEncryption', 'smtpEncryption'], 'in', 'range' => ImapHelper::ENCRYPTION],
            [['imapPassword', 'smtpPassword'], 'string'],
        ];
    }

    /**
     * Устанавливает параметры серверов на основе указанного адреса электронной почты
     *
     * @param string $email
     */
    public function setEmail(string $email)
    {
        if ($this->imapEmail === null) {
            $this->imapEmail = $email;
        }
        if ($this->smtpEmail === null) {
            $this->smtpEmail = $email;
        }
        $host = substr($email, strrpos($email, '@') + 1);
        if (!$host) {
            return;
        }
        if (isset(self::DEFAULT_SERVER_CONFIGURATION[$host]) === false) {
            return;
        }
        $config = self::DEFAULT_SERVER_CONFIGURATION[$host];
        if ($this->imapHost === null && isset($config['imapHost']) === true) {
            $this->imapHost = $config['imapHost'];
        }
        if (isset($config['imapEncryption']) === true) {
            $this->imapEncryption = $config['imapEncryption'];
        }
        if ($this->imapPort === null && isset($config['imapPort']) === true) {
            $this->imapPort = $config['imapPort'];
        }
        if ($this->smtpHost === null && isset($config['smtpHost']) === true) {
            $this->smtpHost = $config['smtpHost'];
        }
        if (isset($config['smtpEncryption']) === true) {
            $this->smtpEncryption = $config['smtpEncryption'];
        }
        if ($this->smtpPort === null && isset($config['smtpPort']) === true) {
            $this->smtpPort = $config['smtpPort'];
        }
    }

    /** @inheritDoc */
    public function attributeLabels()
    {
        return [
            'imapHost' => 'Сервер входящей почты (IMAP)',
            'imapPort' => 'IMAP порт',
            'imapEncryption' => 'Защищённое соединение (SSL)',
            'imapEmail' => 'Адрес электронной почты',
            'imapPassword' => 'Пароль',
            'smtpHost' => 'Сервер исходящей почты (SMTP)',
            'smtpPort' => 'SMTP порт',
            'smtpEncryption' => 'Защищённое соединение (SSL)',
            'smtpEmail' => 'Адрес электронной почты',
            'smtpPassword' => 'Пароль',
        ];
    }

    /**
     * Выполняет валидацию и сохраняет конфигурацию в базе данных
     *
     * @param Employee $employee
     * @param bool     $tryImapConnection Попытаться подключиться к IMAP для проверки соединения
     * @return bool
     */
    public function storeToUser(Employee $employee, bool $tryImapConnection = true)
    {
        if ($this->validate() !== true) {
            return false;
        }
        if ($tryImapConnection === true && $this->validateConnection() === false) {
            return false;
        }
        $integrationEmail = $employee->integration(Employee::INTEGRATION_EMAIL);
        if ($integrationEmail === null || isset($integrationEmail['date']) === false) {
            $this->date = time();
        } else {
            $this->date = $integrationEmail['date'];
        }
        return $employee->saveIntegration(Employee::INTEGRATION_EMAIL, $this->toArray());
    }

    /**
     * Пытается подключиться к IMAP, чтобы проверить соединение
     *
     * @return bool
     */
    private function validateConnection(): bool
    {
        try {
            $imap = new ImapHelper($this->toArray());
            $imap->statusMailbox();
        } catch (Exception $e) {
            $message = $e->getMessage();
            if (strpos($message, 'AUTHENTICATIONFAILED') !== null) {
                $this->addError('imapEmail', $message);
            } else {
                $this->addError('imapHost', $message);
            }
            return false;
        }
        return true;
    }

}
