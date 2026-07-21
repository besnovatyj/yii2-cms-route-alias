<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

/**
 * Зависимости модуля (формат {@see \Besnovatyj\Contracts\module\ProvidesDependencies}).
 *
 * Модуль намеренно самодостаточен: не требует других модулей. Интеграция с модулями-провайдерами
 * целей ({@see \Besnovatyj\Contracts\routing\AliasTargetProvider}) — опциональна и мягкая (через
 * контракт), поэтому в жёсткие зависимости не выносится.
 */
return [
    'modules' => [],
];
