<?php

namespace frontend\modules\integration\models;

use common\models\document\EmailFile;
use common\models\employee\Employee;
use Exception;
use Swift_SmtpTransport;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\swiftmailer\Mailer;
use yii\web\ForbiddenHttpException;

/**
 * Форма отправки письма
 *
 */
class EmailLetterForm extends Model
{

    /** @var string E-mail от кого */
    public $from;
    /** @var string E-mail кому */
    public $to;
    /** @var string Тема письма */
    public $subject;
    /** @var string Текст письма */
    public $body;
    /** @var array Вложения */
    public $attachments = [];
    /** @var string E-mail для ответа */
    public $replyTo;

    public function __construct(array $mail = null, bool $bodyHtml = true)
    {
        if ($mail !== null) {
            $this->from = $mail['fromEmail'];
            $this->to = $mail['to'];
            $this->subject = $mail['subject'];
            $this->body = ($bodyHtml === true ? $mail['textHtml'] ?? $mail['textPlain'] : $mail['textPlain']);
            $this->attachments = $mail['attachments'];
            if ($mail['replyTo']) {
                if (is_array($mail['replyTo']) === true) {
                    $mail['replyTo'] = array_shift($mail['replyTo']);
                }
                $this->replyTo = $mail['replyTo'];
            }
        }
        parent::__construct();
    }

    /** @inheritDoc */
    public function rules(): array
    {
        return [
            [['from', 'to', 'subject', 'body'], 'required'],
            [['from', 'replyTo'], 'email'],
            [
                ['to', 'attachments'],
                'filter',
                'filter' => function ($value) {
                    return is_string($value) ? explode(',', $value) : $value;
                },
            ],
            ['to', 'each', 'rule' => ['email']],
            ['attachments', 'each', 'rule' => ['filter', 'filter' => 'trim']],
            [['subject', 'body'], 'string'],
//            ['attachments']
        ];
    }

    /** @inheritDoc */
    public function attributeLabels()
    {
        return [
            'from' => 'От кого (e-mail)',
            'to' => 'Кому (e-mail)',
            'subject' => 'Тема',
            'body' => 'Текст письма',
        ];
    }

    /**
     * Отправляет письмо по протоколу SMTP
     *
     * @param bool       $validate Нужно ли предварительно проверить валидность формы
     * @param array|null $config   Массив конфигурации SMTP, если не задано, будет взято у текущего пользователя
     * @return bool Была ли отправка письма успешной
     * @throws ForbiddenHttpException
     * @throws InvalidConfigException
     */
    public function send(bool $validate = true, array $config = null)
    {
        if ($validate === true) {
            if ($this->validate() === false) {
                return false;
            }
        }
        if ($config === null) {
            /** @var Employee $employee */
            $employee = Yii::$app->user->identity;
            /** @var array|null $integrationEmail */
            $config = $employee->integration(Employee::INTEGRATION_EMAIL);
        }
        $transport = new Swift_SmtpTransport($config['smtpHost'], $config['smtpPort'], $config['smtpEncryption']);
        $transport->setUsername($config['smtpEmail']);
        $transport->setPassword($config['smtpPassword']);

        $mailer = new Mailer();
        $mailer->setTransport($transport);
        try {
            $mailer = $mailer->compose()
                //Неправильный e-mail отправителя может привести к блокировке отправки SMTP-сервером
                ->setFrom($config['smtpEmail'])
                ->setTo(array_shift($this->to))
                ->setSubject($this->subject)
                ->setHtmlBody($this->body);
            if ($this->replyTo) {
                $mailer->setReplyTo($this->replyTo);
            }
            if (count($this->to) > 0) {
                $mailer->setCc($this->to);
            }
            foreach ($this->_resolveAttachments() as $item) {
                $mailer->attach($item['filePath'], ['fileName' => $item['name']]);
            }
            return $mailer->send();
        } catch (Exception $e) {
            throw new ForbiddenHttpException($e->getMessage());
        }
    }

    private function _resolveAttachments()
    {
        $attach = [];
        foreach ($this->attachments as $item) {
            if (ctype_digit($item) === true) {
                $emailFile = EmailFile::findOne($item);
                if ($emailFile === null) {
                    continue;
                }
                $attach[] = [
                    'filePath' => $emailFile->getFilePath() . DIRECTORY_SEPARATOR . $emailFile->file_name,
                    'name' => $emailFile->file_name,
                ];
            } elseif (is_array($item) === true) {
                $attach[] = $item;
            }
        }
        return $attach;
    }
}
