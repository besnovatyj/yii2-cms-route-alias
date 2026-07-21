<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

use Besnovatyj\RouteAlias\entities\RouteAlias;
use Besnovatyj\RouteAlias\forms\backend\RouteAliasForm;
use yii\web\View;

/* @var $this View */
/* @var $model RouteAliasForm */
/* @var $alias RouteAlias */
/* @var $catalog array */

$this->title = 'Редактирование алиаса: /' . $alias->path;
$this->params['breadcrumbs'][] = ['label' => 'URL Aliases', 'url' => ['index']];
$this->params['breadcrumbs'][] = 'Редактирование';
?>
<?= $this->render('_form', ['model' => $model, 'catalog' => $catalog]) ?>
