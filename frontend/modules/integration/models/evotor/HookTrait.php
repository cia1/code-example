<?php

namespace frontend\modules\integration\models\evotor;

/**
 * Выполняет обработку веб-хуков эвотора
 */
trait HookTrait
{

    /**
     * Меняет ключи в данных веб-хука в соответствии static::hookDataAlias()
     *
     * @param array $data
     * @return array
     */
    public static function replaceAliases(array $data): array
    {
        foreach (static::hookDataAlias() as $src => $dst) {
            if (isset($data[$src]) === true) {
                $data[$dst] = $data[$src];
                unset($data[$src]);
            }
        }
        return $data;
    }

    /**
     * Обработчик веб-хука для каждой отдельной сущности
     *
     * @param int    $companyId Идентификатор компании КУБ
     * @param array  $data      Данные веб-хука
     * @param string $path      Запрошенный URL-адрес (может содержать важные GET-параметры)
     * @return bool Удалось ли создать/обновить запись в БД
     */
    public function hook(int $companyId, array $data, string $path): bool
    {
        $data = static::prepareData($data);
        $this->load($data, '');
        if ($this->hasAttribute('uuid') === true) {
            $this->uuid = $data['id'] ?? $data['uuid'] ?? null;
        }
        if ($this->hasAttribute('id') === true) {
            $this->id = null;
        }
        if ($this->hasAttribute('company_id') === true) {
            $this->company_id = $companyId;
        }
        $this->parsePath($path);
        return $this->save();
    }

    /**
     * Обрабатывает данные полученные от Эвотор прежде чем загрузить в модель.
     * Перегрузите этот метод в модели
     *
     * @param array $data Данные, полученные от Эвотор
     * @return array Обработанные данные
     */
    protected static function prepareData(array $data): array
    {
        return $data;
    }

    /**
     * Извлекает дополнительные данные из URL.
     * Модели должны переопределить этот метод, если в URL содержатся какие-либо данные
     *
     * @param string $path
     */
    protected function parsePath(string $path)
    {
    }

}