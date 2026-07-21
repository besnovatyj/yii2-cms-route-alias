<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

use Besnovatyj\RouteAlias\Module;
use Besnovatyj\RouteAlias\urls\RouteAliasUrlRule;

/**
 * Yii2-конфиг модуля для движка yiisoft/config (группа `common` — общий для всех приложений).
 *
 * Объявляется через `extra.config-plugin`, собирается modman в merge-plan и мёржится в рантайме.
 * Регистрирует модуль, вкладывает класс-правило в `frontendUrlManager` и bootstrap (инвалидация кэша).
 *
 * URL-правило — вклад в именованный компонент `frontendUrlManager` (как у Blog): группа `common`
 * мёржится во все приложения, `RecursiveMerge` конкатенирует `rules` — вклад встаёт ПЕРЕД правилами
 * root, а catch-all ядра остаётся последним. Правило гейтится modman: деактивация модуля убирает его
 * из сборки, и маршрутизация возвращается к обычной (прозрачный fallback). Правило возвращает `false`
 * на не-алиасных путях, поэтому не затеняет остальные правила.
 */
return [
    'modules' => [
        Module::moduleId() => array_merge(
            ['class' => Module::class],
            Module::moduleConfig(),
            ['version' => Module::moduleVersion()],
        ),
    ],
    'components' => [
        'frontendUrlManager' => [
            'rules' => [
                ['class' => RouteAliasUrlRule::class],
            ],
        ],
    ],
    // L2-bootstrap: сброс кэша карт при изменении алиасов (см. Bootstrap).
    'bootstrap' => array_values(Module::bootstrapClasses()),
];
