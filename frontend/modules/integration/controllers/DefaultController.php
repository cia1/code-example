<?php

namespace frontend\modules\integration\controllers;

use common\components\filters\AccessControl;
use common\components\helpers\ArrayHelper;
use common\models\employee\Employee;
use common\models\jobs\Jobs;
use frontend\components\FrontendController;
use frontend\modules\integration\models\moneta\GetOperationsForm;
use frontend\rbac\UserRole;
use Yii;

/**
 * Настройка интеграции разных модулей
 * Контроллер реализует только список модулей, настрйки и интерфейс по каждому модулю реализуется в "своём" контроллере.
 */
class DefaultController extends FrontendController
{

    /**
     * @return array
     */
    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                        // Удалить условие, когда задача будет принята
                        'matchCallback' => function ($rule, $action) {
                            return YII_ENV_DEV || in_array(Yii::$app->user->identity->company->id, [486, 23083, 1, 1119]);
                        },
                    ],
                ],
            ],
        ]);
    }

    /**
     * @return string
     */
    public function actionIndex(): string
    {
        $employee = Yii::$app->user->identity;
        /** @var Employee $employee */
        $config = $employee->company->integration;
        $hasNotFinishedMonetaJob = Jobs::find()
            ->andWhere(['company_id' => $employee->currentEmployeeCompany->company_id])
            ->andWhere(['type' => Jobs::TYPE_EXPORT_OPERATIONS_FROM_MONETA])
            ->andWhere(['finished_at' => null])
            ->exists();
        $popupMessage = Yii::$app->session->get('popupMessage');
        if ($popupMessage !== null) {
            Yii::$app->session->remove('popupMessage');
        }
        return $this->render('index', [
            'user' => $employee,
            'canManage' => Yii::$app->user->can(UserRole::ROLE_CHIEF),
            'popupMessage' => $popupMessage,
            'email' => [
                'active' => is_array($config[Employee::INTEGRATION_EMAIL] ?? null),
                'configured' => is_array($config[Employee::INTEGRATION_EMAIL] ?? null),
            ],
            'amocrm' => [
                'active' => is_array($config[Employee::INTEGRATION_AMOCRM] ?? null),
                'configured' => is_array($config[Employee::INTEGRATION_AMOCRM] ?? null),
            ],
            'facebook' => [
                'active' => Yii::$app->request->cookies->get('integrationFBUser-' . $employee->company_id) && Yii::$app->request->cookies->get('integrationFBToken-' . $employee->company_id),
            ],
            'evotor' => [
                'active' => $config[Employee::INTEGRATION_EVOTOR] ?? false,
                'configured' => $config[Employee::INTEGRATION_EVOTOR] ?? false,
            ],
            'vk' => [
                'active' => is_array($config[Employee::INTEGRATION_VK] ?? null),
                'configured' => is_array($config[Employee::INTEGRATION_VK] ?? null),
            ],
            'moneta' => [
                'active' => is_array($config[Employee::INTEGRATION_MONETA] ?? null),
                'hasNotFinishedMonetaJob' => $hasNotFinishedMonetaJob,
            ],
            'unisender' => [
                'active' => is_array($config[Employee::INTEGRATION_UNISENDER] ?? null),
            ],
            'insales' => [
                'active' => $config[Employee::INTEGRATION_INSALES] ?? false,
                'configured' => $config[Employee::INTEGRATION_INSALES] ?? false,
            ],
            'bitrix24' => [
                'active' => isset($config[Employee::INTEGRATION_BITRIX24]),
                'configured' => isset($config[Employee::INTEGRATION_BITRIX24]),
            ],
            'googleAds' => [
                'active' => is_array($config[Employee::INTEGRATION_GOOGLE_ADS] ?? null),
            ],
            'dreamkas' => [
                'active' => true,
                'configured' => is_array($config[Employee::INTEGRATION_DREAMKAS] ?? null)
            ]
        ]);
    }

}
