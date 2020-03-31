<?php
/**
 * Created by PhpStorm.
 * User: Валик
 * Date: 04.02.2020
 * Time: 21:24
 */

use common\components\helpers\Html;

/**
 * @var $text string|null
 * @var $class string|null
 * @var $url string|null
 * @var $style string|null
 * @var $canManage bool
 */

$style = $style ?? null;
$class = $class ?? null;

echo Html::a($text, $canManage ? $url : null, [
    'class' => $class,
    'style' => $style,
    'data' => [
        'toggle' => $canManage ? null : 'modal',
        'target' => $canManage ? null : '#integration-not-allowed',
    ],
]);
