<?php

namespace frontend\modules\integration\models\bitrix24;

use common\models\bitrix24\Vat as VatCommon;
use yii\httpclient\Exception;

/**
 * @mixin HookTrait
 */
class Vat extends VatCommon
{
    use HookTrait;

    /**
     * @param int $id
     * @return mixed|null
     * @throws Exception
     */
    public function loadFromRest(int $id)
    {
        return $this->helper->rest('crm.vat.get', ['ID' => $id]);
    }

}