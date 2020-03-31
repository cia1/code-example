<?php

namespace frontend\modules\integration;

use common\components\filters\AccessControl;
use common\components\helpers\ArrayHelper;
use Yii\base\Module as ModuleBase;

/**
 * Интеграции
 * В свойстве Company::$integration хранятся настройки интеграция для каждой компании (JSON).
 */
class Module extends ModuleBase
{
    /** @inheritDoc */
    public $controllerNamespace = 'frontend\modules\integration\controllers';

    /** @inheritDoc */
    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
                'except' => [
                    'amocrm/hook',
                    'evotor/hook',
                    'insales/install',
                    'insales/hook',
                    'insales/destroy',
                    'bitrix24/hook',
                ],
            ],
        ]);
    }

}
