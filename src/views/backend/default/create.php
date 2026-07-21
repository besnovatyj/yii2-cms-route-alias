<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

use Besnovatyj\RouteAlias\forms\backend\RouteAliasForm;
use yii\web\View;

/* @var $this View */
/* @var $model RouteAliasForm */
/* @var $catalog array */

$this->title = 'Новый URL-алиас';
$this->params['breadcrumbs'][] = ['label' => 'URL Aliases', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<?= $this->render('_form', ['model' => $model, 'catalog' => $catalog]) ?>
