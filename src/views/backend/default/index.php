<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

use Besnovatyj\Backend\Widgets\grid\ActionColumn;
use Besnovatyj\Backend\Widgets\pagination\LinkPager;
use Besnovatyj\Kernel\security\AccessHelper;
use Besnovatyj\RouteAlias\entities\RouteAlias;
use Besnovatyj\RouteAlias\forms\backend\search\RouteAliasSearch;
use yii\data\ActiveDataProvider;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\web\View;

/* @var $this View */
/* @var $searchModel RouteAliasSearch */
/* @var $dataProvider ActiveDataProvider */

$this->title = 'URL Aliases';
$this->params['breadcrumbs'][] = $this->title;
?>

<p>
    <?= Html::a('Создать алиас', ['create'], ['class' => 'btn btn-success']) ?>
</p>

<div class="card">
    <div class="card-header"><?= Html::encode($this->title) ?></div>
    <div class="card-body">
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'filterModel' => $searchModel,
            'layout' => "{summary}\n{items}",
            'columns' => [
                [
                    'attribute' => 'path',
                    'value' => static function (RouteAlias $model) {
                        return Html::a(Html::encode('/' . $model->path), ['view', 'id' => $model->id]);
                    },
                    'format' => 'raw',
                ],
                'route',
                [
                    'attribute' => 'slug',
                    'filter' => false,
                    'value' => static function (RouteAlias $model) {
                        $params = $model->getParams();
                        return $params === [] ? '—' : implode(', ', array_map(
                            static fn ($k, $v) => $k . '=' . $v,
                            array_keys($params),
                            $params
                        ));
                    },
                ],
                [
                    'attribute' => 'status',
                    'filter' => [RouteAlias::STATUS_INACTIVE => 'Выключен', RouteAlias::STATUS_ACTIVE => 'Включён'],
                    'value' => static function (RouteAlias $model) {
                        return $model->isActive()
                            ? '<span class="badge bg-success">Включён</span>'
                            : '<span class="badge bg-secondary">Выключен</span>';
                    },
                    'format' => 'raw',
                ],
                [
                    'class' => ActionColumn::class,
                    'template' => AccessHelper::filterActionColumn(['view', 'update', 'delete']),
                ],
            ],
        ]) ?>
    </div>
    <div class="card-footer clearfix">
        <nav aria-label="" class="nav-pagination">
            <?= LinkPager::widget(['pagination' => $dataProvider->getPagination()]) ?>
        </nav>
    </div>
</div>
