<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

use Besnovatyj\RouteAlias\entities\RouteAlias;
use yii\helpers\Html;
use yii\web\View;
use yii\widgets\DetailView;

/* @var $this View */
/* @var $alias RouteAlias */

$this->title = '/' . $alias->path;
$this->params['breadcrumbs'][] = ['label' => 'URL Aliases', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<p>
    <?= Html::a('Редактировать', ['update', 'id' => $alias->id], ['class' => 'btn btn-primary']) ?>
    <?= Html::a('Удалить', ['delete', 'id' => $alias->id], [
        'class' => 'btn btn-danger',
        'data' => ['confirm' => 'Удалить алиас?', 'method' => 'post'],
    ]) ?>
</p>

<div class="card">
    <div class="card-header">Алиас</div>
    <div class="card-body">
        <?= DetailView::widget([
            'model' => $alias,
            'attributes' => [
                'id',
                [
                    'attribute' => 'path',
                    'value' => '/' . $alias->path,
                ],
                'route',
                [
                    'attribute' => 'params',
                    'label' => 'Параметры',
                    'value' => $alias->params_json ?? '—',
                ],
                [
                    'attribute' => 'status',
                    'value' => $alias->isActive() ? 'Включён' : 'Выключен',
                ],
                'created_at',
                'updated_at',
            ],
        ]) ?>
    </div>
    <div class="card-footer clearfix"></div>
</div>
