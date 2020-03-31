<?php

namespace frontend\modules\integration\models;

use common\components\date\DateHelper;
use common\components\date\DatePickerFormatBehavior;
use common\components\helpers\ArrayHelper;
use common\models\employee\Employee;
use Yii;
use yii\base\Model;

/**
 * Фильтр отчёта по дате
 */
class DateFromToFilterForm extends Model
{

    public $dateFrom;

    public $dateTo;

    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            [
                'class' => DatePickerFormatBehavior::class,
                'attributes' => [
                    'dateFrom' => [
                        'message' => 'Дата начала указана неверно.',
                    ],
                    'dateTo' => [
                        'message' => 'Дата окончания указана неверно.',
                    ],
                ],
                'dateFormatParams' => [
                    'whenClient' => 'function(){}',
                ],
            ],
        ]);
    }

    public function rules()
    {
        return [
            [
                'dateFrom',
                'default',
                'value' => function () {
                    return static::defaultDateFrom();
                },
            ],
            [
                'dateTo',
                'default',
                'value' => function () {
                    return static::defaultDateTo();
                },
            ],
            [['dateFrom', 'dateTo'], 'required', 'message' => 'Необходимо заполнить.'],
            [['dateFrom'], 'validateDateFrom'],
            [['dateTo'], 'validateDateTo'],
        ];
    }

    public function validateDateTo($attribute)
    {
        if ($this->dateFrom > $this->dateTo) {
            $this->addError($attribute, 'Дата окончания должна быть больше даты начала.');
        }
    }

    public function validateDateFrom($attribute)
    {
        if ($this->dateFrom > date(DateHelper::FORMAT_DATE)) {
            $this->addError($attribute, 'Дата не должна быть больше текущей даты.');
        }
    }

    public function resetDate()
    {
        $this->dateFrom = static::defaultDateFrom();
        $this->dateTo = static::defaultDateTo();
    }

    protected static function defaultDateFrom(): string
    {
        /** @var Employee $employee */
        $employee = Yii::$app->user->identity;
        if ($employee->getIsSetStatisticRange() === true) {
            return $employee->getStatisticRangeDates('from');
        }
        return date('Y-m-01');
    }

    protected static function defaultDateTo(): string
    {
        /** @var Employee $employee */
        $employee = Yii::$app->user->identity;
        if ($employee->getIsSetStatisticRange() === true) {
            return $employee->getStatisticRangeDates('to');
        }
        return date('Y-m-d');
    }
}