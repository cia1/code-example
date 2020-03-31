<?php

namespace frontend\modules\integration\controllers;

use common\models\employee\Employee;
use frontend\components\FrontendController;
use Yii;

/**
 * Интеграция dreamkas.ru
 */
class DreamkasController extends FrontendController
{

    /**
     * Настройки
     * Настройки -> Интеграция -> Dreamkas -> Настройки
     */
    public function actionSetting()
    {
        /** @var Employee $employee */
        $employee = Yii::$app->user->identity;
        return $this->render('setting', [
            'count' => count($employee->companies),
            'company' => $employee->company->getShortName(),
        ]);
    }


}