<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

use Besnovatyj\RouteAlias\forms\backend\RouteAliasForm;
use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;
use yii\helpers\Json;
use yii\web\View;

/* @var $this View */
/* @var $model RouteAliasForm */
/* @var $catalog array<string,array{label:string,targets:array,slugs:array}> */

// Определяем текущий модуль по роуту (на редактировании module в форме пуст).
$currentModule = $model->module;
if ($currentModule === '' && $model->route !== '') {
    foreach ($catalog as $moduleId => $data) {
        foreach ($data['targets'] as $t) {
            if ($t['route'] === $model->route) {
                $currentModule = (string)$moduleId;
                break 2;
            }
        }
    }
}

$moduleItems = [];
foreach ($catalog as $moduleId => $data) {
    $moduleItems[$moduleId] = $data['label'];
}

$dataId = 'route-alias-form';
?>

<?php $form = ActiveForm::begin(); ?>
<div class="card" id="<?= $dataId ?>"
     data-catalog='<?= Json::htmlEncode($catalog) ?>'
     data-current-module="<?= Html::encode($currentModule) ?>"
     data-current-route="<?= Html::encode($model->route) ?>"
     data-current-slug="<?= Html::encode((string)$model->slug) ?>">
    <div class="card-header"><?= Html::encode($this->title) ?></div>
    <div class="card-body">

        <?php if ($catalog === []): ?>
            <div class="alert alert-warning">
                Ни один модуль не объявил алиасуемых целей. Установите/включите модуль, реализующий
                <code>AliasTargetProvider</code> (например, Page), чтобы создавать короткие URL.
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-12 col-md-4">
                <?= $form->field($model, 'module')->dropDownList(
                    $moduleItems,
                    ['prompt' => '— выберите модуль —', 'data-role' => 'module']
                ) ?>
            </div>
            <div class="col-12 col-md-4">
                <?= $form->field($model, 'route')->dropDownList(
                    $model->route !== '' ? [$model->route => $model->route] : [],
                    ['prompt' => '— выберите цель —', 'data-role' => 'route']
                ) ?>
            </div>
            <div class="col-12 col-md-4">
                <?= $form->field($model, 'slug')->dropDownList(
                    ($model->slug !== null && $model->slug !== '') ? [$model->slug => $model->slug] : [],
                    ['prompt' => '— выберите slug —', 'data-role' => 'slug']
                ) ?>
            </div>
        </div>

        <?= $form->field($model, 'isHome')->checkbox(['data-role' => 'home'])
            ->hint('Цель открывается по адресу «/». Главная может быть только одна.') ?>

        <?= $form->field($model, 'path')->textInput([
            'maxlength' => true,
            'data-role' => 'path',
            'placeholder' => 'about-the-manufacturer',
            'disabled' => $model->isHome,
        ])->hint('Короткий адрес без домена и без ведущего слэша. Пусто — подставится выбранный slug.') ?>

        <?= $form->field($model, 'status')->checkbox() ?>

    </div>
    <div class="card-footer clearfix">
        <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success']) ?>
        <?= Html::a('Отмена', ['index'], ['class' => 'btn btn-secondary']) ?>
    </div>
</div>
<?php ActiveForm::end(); ?>

<?php
// Каскад модуль → цель → slug целиком на встроенных данных, без запросов к серверу и без доп. ассетов.
$js = <<<'JS'
(function () {
    var root = document.getElementById('route-alias-form');
    if (!root) { return; }
    var catalog = JSON.parse(root.getAttribute('data-catalog') || '{}');
    var moduleSel = root.querySelector('[data-role="module"]');
    var routeSel = root.querySelector('[data-role="route"]');
    var slugSel = root.querySelector('[data-role="slug"]');
    var pathInput = root.querySelector('[data-role="path"]');
    var homeBox = root.querySelector('[data-role="home"]');

    function isHome() { return !!(homeBox && homeBox.checked); }

    function fill(select, items, selected, prompt) {
        select.innerHTML = '';
        var opt = document.createElement('option');
        opt.value = '';
        opt.textContent = prompt;
        select.appendChild(opt);
        Object.keys(items).forEach(function (value) {
            var o = document.createElement('option');
            o.value = value;
            o.textContent = items[value];
            if (value === selected) { o.selected = true; }
            select.appendChild(o);
        });
    }

    function targetsOf(moduleId) {
        var map = {};
        var mod = catalog[moduleId];
        if (mod) { mod.targets.forEach(function (t) { map[t.route] = t.label; }); }
        return map;
    }

    function slugsOf(moduleId, route) {
        var mod = catalog[moduleId];
        return (mod && mod.slugs && mod.slugs[route]) ? mod.slugs[route] : {};
    }

    function onModuleChange(selRoute, selSlug) {
        fill(routeSel, targetsOf(moduleSel.value), selRoute || '', '— выберите цель —');
        onRouteChange(selSlug);
    }

    function onRouteChange(selSlug) {
        fill(slugSel, slugsOf(moduleSel.value, routeSel.value), selSlug || '', '— выберите slug —');
    }

    moduleSel.addEventListener('change', function () { onModuleChange('', ''); });
    routeSel.addEventListener('change', function () { onRouteChange(''); });
    slugSel.addEventListener('change', function () {
        if (pathInput && !isHome() && pathInput.value.trim() === '' && slugSel.value) {
            pathInput.value = slugSel.value;
        }
    });
    // Главная: путь хранится пустым (сервер обнуляет его сам), поле выключено и не отправляется.
    if (homeBox && pathInput) {
        homeBox.addEventListener('change', function () {
            pathInput.disabled = isHome();
            if (isHome()) {
                pathInput.value = '';
            } else if (pathInput.value.trim() === '' && slugSel.value) {
                pathInput.value = slugSel.value;
            }
        });
    }

    // Инициализация из текущих значений (создание/редактирование).
    var curModule = root.getAttribute('data-current-module') || '';
    var curRoute = root.getAttribute('data-current-route') || '';
    var curSlug = root.getAttribute('data-current-slug') || '';
    if (curModule) {
        moduleSel.value = curModule;
        onModuleChange(curRoute, curSlug);
    }
})();
JS;
$this->registerJs($js, View::POS_END);
