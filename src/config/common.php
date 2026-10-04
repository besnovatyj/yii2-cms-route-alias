<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

use Besnovatyj\RouteAlias\Module;

/**
 * Yii2-конфиг модуля для движка yiisoft/config (группа `common` — общий для всех приложений).
 *
 * Объявляется через `extra.config-plugin`, собирается modman в merge-plan и мёржится в рантайме.
 * Регистрирует модуль и bootstrap.
 *
 * URL-правило намеренно НЕ вкладывается в `components.frontendUrlManager.rules`: вклады мёржатся по
 * алфавиту пакетов, и правила модулей-провайдеров (`page/<slug>`) оказывались раньше и перехватывали
 * генерацию URL. Правило ставится первым из Bootstrap (см. Bootstrap::prependAliasRule()). Гейт modman
 * сохраняется: деактивация модуля убирает Bootstrap, и маршрутизация возвращается к обычной.
 */
return [
    'modules' => [
        Module::moduleId() => array_merge(
            ['class' => Module::class],
            Module::moduleConfig(),
        ),
    ],
    // L2-bootstrap: сброс кэша карт, правило алиасов первым в frontendUrlManager, 301 на алиас (см. Bootstrap).
    'bootstrap' => array_values(Module::bootstrapClasses()),
];
