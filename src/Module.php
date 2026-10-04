<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\RouteAlias;

use Besnovatyj\Contracts\module\DeclaresModule;
use Besnovatyj\Contracts\module\ProvidesBootstrap;
use Besnovatyj\Contracts\module\ProvidesDependencies;
use Besnovatyj\Contracts\module\ProvidesMigrations;
use Besnovatyj\Contracts\module\ProvidesOptions;
use Besnovatyj\Kernel\module\CmsModule;

/**
 * Модуль управляемых из админки коротких URL-алиасов фронтенда.
 *
 * Ставит своё правило первым в `frontendUrlManager` (см. Bootstrap) и даёт бэкенд CRUD. Опционален:
 * при выключении правило исчезает из сборки и маршрутизация возвращается к обычной. См. PLAN.md.
 */
class Module extends CmsModule implements
    DeclaresModule, ProvidesBootstrap,
    ProvidesDependencies, ProvidesMigrations, ProvidesOptions
{
    public const bool EDITABLE = true;
    public const string MODULE_ID = 'RouteAlias';

    public static function moduleId(): string { return self::MODULE_ID; }
    public static function isEditable(): bool { return self::EDITABLE; }
    public static function moduleConfig(): array { return require __DIR__ . '/config/config.php'; }
    public static function options(): array { return require __DIR__ . '/config/options.php'; }
    public static function dependencies(): array { return require __DIR__ . '/config/dependencies.php'; }
    public static function migrationPath(): string { return __DIR__ . '/migrations'; }
    public static function migrationNamespace(): ?string { return __NAMESPACE__ . '\\migrations'; }
    public static function bootstrapClasses(): array { return [Bootstrap::class]; }
}
