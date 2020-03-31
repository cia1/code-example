<?php

namespace frontend\modules\integration\helpers;

use unyii2\imap\ImapConnection;
use unyii2\imap\Mailbox;
use Yii;
use yii\base\Exception as BaseException;
use yii\base\InvalidConfigException;
use yii\helpers\FileHelper;
use yii\web\NotFoundHttpException;
use unyii2\imap\Exception;

/**
 * Помощник по работе с IMAP
 */
class ImapHelper extends Mailbox
{
    const ENCRYPTION_NO = 'no';
    const ENCRYPTION_SSLTLS = 'ssl';
    const ENCRYPTION = [self::ENCRYPTION_NO, self::ENCRYPTION_SSLTLS]; //Список доступных методов шифрования

    const CACHE_ACTUAL_ID = 'imap-actual-{companyId}';
    const CACHE_ACTUAL_TIME = 180;
    const CACHE_MAIL_LIST_ID = 'imap-maillist-{companyId}';
    const CACHE_MAIL_LIST_TIME = 7200;

    private $_companyId;
    /** @var int TIMESTAMP-дата подключения интеграции */
    private $_date;
    private $_sortReverse = true;

    /**
     * @param array $config @see \common\models\Employee::integration()
     * @param int   $companyId
     * @param bool  $sortReverse
     * @throws InvalidConfigException
     * @throws BaseException
     */
    public function __construct(array $config, int $companyId = null, bool $sortReverse = true)
    {
        $this->_companyId = $companyId;
        if (
            isset($config['imapHost']) !== true
            || isset($config['imapPort']) !== true
            || isset($config['imapEncryption']) !== true
            || isset($config['imapEmail']) !== true
            || isset($config['imapPassword']) !== true) {
            throw new InvalidConfigException('IMAP server not configured');
        }
        $this->_sortReverse = $sortReverse;
        $this->_date = $config['date'];
        $imapPath = '{' . $config['imapHost'] . ':' . $config['imapPort'] . '/imap';
        if ($config['imapEncryption'] !== self::ENCRYPTION_NO) {
            $imapPath .= '/' . $config['imapEncryption'];
        }
        $imapPath .= '}INBOX';
        $connection = new ImapConnection;
        $connection->imapPath = $imapPath;
        $connection->imapLogin = $config['imapEmail'] ?? null;
        $connection->imapPassword = $config['imapPassword'] ?? null;
        $connection->serverEncoding = 'encoding';
        if ($companyId !== null) {
            $connection->attachmentsDir = self::getMailPath($companyId);
        }
        parent::__construct($connection);
    }

    /**
     * Возвращает URL иконки по имени файла
     *
     * @param string $fileName
     * @return string
     */
    public static function iconByFileName(string $fileName)
    {
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        switch ($ext) {
            case 'xls':
                return '/img/email/emailXls.png';
            case 'xlsx':
                return '/img/email/emailXls.png';
            case 'pdf':
                return '/img/email/emailPdf.png';
            case 'txt':
                return '/img/email/emailTxt.png';
            case 'doc':
                return '/img/email/emailDoc.png';
            case 'docx':
                return '/img/email/emailDoc.png';
            case 'zip':
                return '/img/email/emailZip.png';
            default:
                return '/img/email/emailUnknown.png';
        }
    }

    public function setSortReverse()
    {
        $this->_sortReverse = !$this->_sortReverse;
        $cacheActualId = str_replace('{companyId}', $this->_companyId, self::CACHE_ACTUAL_ID);
        Yii::$app->cache->delete($cacheActualId);
    }


    /**
     * Возвращаеть директорий к папке с вложениями
     *
     * @param int|null $companyId Идентификатор компании, если задан, то вернёт путь к папке этого пользователя
     * @return string
     */
    public static function attachmentPath(int $companyId = null)
    {
        $path = Yii::getAlias('@frontend') . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'integration-mail' . DIRECTORY_SEPARATOR;
        if ($companyId !== null) {
            $path .= $companyId . '/';
        }
        return $path;
    }

    /**
     * Возвращает полное имя файла вложения или NULL, если файла не существует
     *
     * @param int    $companyId
     * @param int    $mailId
     * @param string $attachmentId
     * @param string $fileName
     * @return string|null
     */
    public static function fullAttachmentFileName(int $companyId, int $mailId, string $attachmentId, string $fileName)
    {
        $attachmentId = str_replace(['/', '\\'], '', $attachmentId);
        $fileName = str_replace(['/', '\\'], '', $fileName);
        $fileName = self::attachmentPath($companyId) . $mailId . '_' . $attachmentId . '_' . $fileName;
        return file_exists($fileName) === true ? $fileName : null;
    }

    /**
     * Разделяет e-mail на имя и адрес электронной почты
     *
     * @param string $email
     * @return array
     */
    public static function explodeEmail(string $email)
    {
        if (preg_match('~(.+)<(.+)>~', $email, $tmp)) {
            return [trim($tmp[1]), $tmp[2]];
        } else {
            return [null, $email];
        }
    }

    /**
     * Удаляет письма на удалённом сервере и очищает кеш
     *
     * @param array $IDs
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function delete(array $IDs)
    {
        $cacheActualId = str_replace('{companyId}', $this->_companyId, self::CACHE_ACTUAL_ID);
        $cacheMailListId = str_replace('{companyId}', $this->_companyId, self::CACHE_MAIL_LIST_ID);
        $maillist = $this->getMailListCache();
        foreach ($IDs as $id) {
            if ($this->deleteMail($id)) {
                unset($maillist[$id]);
            }
        }
        $cache = Yii::$app->cache;
        $cache->set($cacheMailListId, $maillist, self::CACHE_MAIL_LIST_TIME);
        $cache->delete($cacheActualId);
    }

    /**
     * Возвращает письма, беря их из кеша или загружая с удалённого сервера
     * Массив содержит ВСЕ UID писем, но данные гарантированно загружены только для указанного диапазона индексов,
     * остальные значения могут быть NULL или содержать загруженные ранее данные и взятые из кеша.
     *
     * @param int $offset Смещение писем, которые нужно загрузить
     * @param int $length Количество писем начиная с $offset
     * @return mixed
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function getMailListCache(int $offset = null, int $length = null)
    {
        if ($this->_companyId === null) {
            throw new InvalidConfigException('You have to set companyId');
        }
        $cacheActualId = str_replace('{companyId}', $this->_companyId, self::CACHE_ACTUAL_ID);
        $cacheMailListId = str_replace('{companyId}', $this->_companyId, self::CACHE_MAIL_LIST_ID);
        $cache = Yii::$app->cache;
        /** @var int[] $IDs Массив идентификаторов писем. Несколько минут хранится в кеше */
        $IDs = $cache->getOrSet($cacheActualId, function () {
            $IDs = $this->searchMailbox($this->_date ? 'SINCE ' . date('d-M-Y', $this->_date) : 'ALL');
            if ($this->_sortReverse === true) {
                $IDs = array_reverse($IDs);
            }
            return $IDs;
        }, self::CACHE_ACTUAL_TIME);
        /** @var array[]|null[] $old Массив данных, где ключ - UID письма. Если значение NULL, значит письмо ещё не было загружено */
        $old = $cache->get($cacheMailListId);
        if ($old === false) {
            $old = [];
        }

        //Объединить ключи новых писем и данные загруженных ранее писем
        $allModels = [];
        $mailbox = []; //тут список UID писем, заголовки которых нужно загрузить с сервера
        $length += $offset;
        foreach ($IDs as $i => $id) {
            if (isset($old[$id]) === true) {
                $allModels[$id] = $old[$id];
            } else {
                $allModels[$id] = null;
                if ($i >= $offset && $i < $length) {
                    $mailbox[] = $id;
                }
            }
        }
        unset($old, $IDs);

        //Загрузить недостающие заголовки писем
        if (count($mailbox) > 0) {
            $mailbox = $this->getMailsInfo($mailbox);
        }
        foreach ($mailbox as $item) {
            $from = self::explodeEmail($item->from);
            $item = [
                'id' => $item->uid,
                'subject' => $item->subject,
                'fromName' => $from[0],
                'fromEmail' => $from[1],
                'date' => date('d.m.Y H:i', strtotime($item->date)),
            ];
            $allModels[$item['id']] = $item;
        }
        $cache->set($cacheMailListId, $allModels, self::CACHE_MAIL_LIST_TIME);
        return $allModels;
    }

    /**
     * Возвращает полную информацию по письму
     * Данные берёт из кеша, а если их нет, то загружает с IMAP-сервера и сохраняет в кеше.
     *
     * @param int $mailId
     * @return mixed
     * @throws NotFoundHttpException
     * @throws Exception
     * @throws InvalidConfigException
     * @throws BaseException
     */
    public function getMailCache(int $mailId)
    {
        if ($this->_companyId === null) {
            throw new InvalidConfigException('You have to set companyId');
        }
        $maillist = $this->getMailListCache();
        if (isset($maillist[$mailId]) === false) {
            throw new NotFoundHttpException('Указанного письма не существует');
        }
        $mail =& $maillist[$mailId];
        if (isset($mail['detail']) === false) {
            array_map('unlink', glob(self::getMailPath($this->_companyId) . '/' . $mail['id'] . '_*'));
            $detail = $this->getMail($mailId, false);
            $mail['detail'] = true;
            $mail['to'] = $detail->toString;
            $mail['replyTo'] = $detail->replyTo;
            $mail['textPlain'] = $detail->textPlain;
            $mail['textHtml'] = $detail->textHtml;
            $attachments = $detail->getAttachments();
            if ($attachments) {
                foreach ($attachments as &$item) {
                    $item = [
                        'id' => $item->id,
                        'name' => $item->name,
                        'filePath' => $item->filePath,
                        'size' => filesize($item->filePath),
                    ];
                }
                $attachments = array_values($attachments);
            }
            $mail['attachments'] = $attachments;
            Yii::$app->cache->set(str_replace('{companyId}', $this->_companyId, self::CACHE_MAIL_LIST_ID), $maillist, self::CACHE_MAIL_LIST_TIME);
        }
        return $mail;
    }

    /**
     * @param int $companyId
     * @return string
     * @throws BaseException
     */
    protected static function getMailPath(int $companyId)
    {
        $path = Yii::getAlias('@runtime') . '/integration-mail/' . $companyId;
        FileHelper::createDirectory($path);
        return $path;
    }

}